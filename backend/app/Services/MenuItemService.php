<?php

namespace App\Services;

use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class MenuItemService
{
    public function list(Restaurant $restaurant, array $filters): Collection
    {
        return $restaurant->menuItems()->select('menu_items.*')
            ->join('categories', 'categories.id', '=', 'menu_items.category_id')
            ->with('category:id,name')
            ->when($filters['category_id'] ?? null, fn ($query, $id) => $query->where('menu_items.category_id', $id))
            ->when($filters['search'] ?? null, function ($query, $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('menu_items.name', 'like', "%{$search}%")
                        ->orWhere('menu_items.description', 'like', "%{$search}%");
                });
            })
            ->when(array_key_exists('is_available', $filters), fn ($query) => $query->where('menu_items.is_available', $filters['is_available']))
            ->when(array_key_exists('is_featured', $filters), fn ($query) => $query->where('menu_items.is_featured', $filters['is_featured']))
            ->orderBy('categories.sort_order')->orderBy('menu_items.sort_order')->orderBy('menu_items.id')
            ->get();
    }

    public function create(Restaurant $restaurant, array $data, ?UploadedFile $image): MenuItem
    {
        unset($data['image']);
        $data['price'] = $this->normalizePrice($data['price']);
        $maxOrder = $restaurant->menuItems()->where('category_id', $data['category_id'])->max('sort_order');
        $data['sort_order'] ??= $maxOrder === null ? 0 : ((int) $maxOrder) + 1;
        $path = $image?->store("menu-items/{$restaurant->id}", 'public');
        if ($path) {
            $data['image'] = $path;
        }

        try {
            return DB::transaction(fn () => $restaurant->menuItems()->create($data)->refresh()->load('category'));
        } catch (Throwable $exception) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            throw $exception;
        }
    }

    public function update(MenuItem $menuItem, array $data, ?UploadedFile $image): MenuItem
    {
        unset($data['image']);
        if (array_key_exists('price', $data)) {
            $data['price'] = $this->normalizePrice($data['price']);
        }
        $oldImage = $menuItem->image;
        $newImage = $image?->store("menu-items/{$menuItem->restaurant_id}", 'public');
        if ($newImage) {
            $data['image'] = $newImage;
        }
        $oldCategoryId = $menuItem->category_id;

        if (isset($data['category_id']) && (int) $data['category_id'] !== $oldCategoryId && ! array_key_exists('sort_order', $data)) {
            $maxOrder = MenuItem::where('restaurant_id', $menuItem->restaurant_id)
                ->where('category_id', $data['category_id'])->max('sort_order');
            $data['sort_order'] = $maxOrder === null ? 0 : ((int) $maxOrder) + 1;
        }

        try {
            DB::transaction(function () use ($menuItem, $data, $oldCategoryId): void {
                $menuItem->update($data);
                if ($menuItem->category_id !== $oldCategoryId) {
                    $this->compactOrdering($menuItem->restaurant_id, $oldCategoryId);
                }
            });
        } catch (Throwable $exception) {
            if ($newImage) {
                Storage::disk('public')->delete($newImage);
            }
            throw $exception;
        }

        if ($newImage && $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return $menuItem->refresh()->load('category');
    }

    public function delete(MenuItem $menuItem): void
    {
        $image = $menuItem->image;
        $restaurantId = $menuItem->restaurant_id;
        $categoryId = $menuItem->category_id;

        DB::transaction(function () use ($menuItem, $restaurantId, $categoryId): void {
            $menuItem->delete();
            $this->compactOrdering($restaurantId, $categoryId);
        });

        if ($image) {
            Storage::disk('public')->delete($image);
        }
    }

    public function reorder(Restaurant $restaurant, int $categoryId, array $menuItemIds): Collection
    {
        return DB::transaction(function () use ($restaurant, $categoryId, $menuItemIds): Collection {
            $ownedIds = $restaurant->menuItems()->where('category_id', $categoryId)->lockForUpdate()->pluck('id')->all();
            $supplied = array_map('intval', $menuItemIds);
            $expected = $ownedIds;
            sort($supplied);
            sort($expected);

            if ($supplied !== $expected) {
                throw ValidationException::withMessages([
                    'menu_item_ids' => ['The list must contain every menu item in the selected category exactly once.'],
                ]);
            }

            foreach ($menuItemIds as $sortOrder => $menuItemId) {
                MenuItem::where('restaurant_id', $restaurant->id)->where('category_id', $categoryId)
                    ->whereKey($menuItemId)->update(['sort_order' => $sortOrder]);
            }

            return $restaurant->menuItems()->where('category_id', $categoryId)->ordered()->with('category:id,name')->get();
        });
    }

    private function compactOrdering(int $restaurantId, int $categoryId): void
    {
        MenuItem::where('restaurant_id', $restaurantId)->where('category_id', $categoryId)
            ->ordered()->pluck('id')
            ->each(fn (int $id, int $position) => MenuItem::whereKey($id)->update(['sort_order' => $position]));
    }

    private function normalizePrice(mixed $price): string
    {
        return number_format((float) $price, 2, '.', '');
    }
}
