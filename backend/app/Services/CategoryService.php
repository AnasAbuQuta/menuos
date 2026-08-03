<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Restaurant;
use App\Support\BilingualContent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    public function create(Restaurant $restaurant, array $data): Category
    {
        return DB::transaction(function () use ($restaurant, $data): Category {
            $data = BilingualContent::synchronize($data);
            $maxOrder = $restaurant->categories()->max('sort_order');
            $data['sort_order'] ??= $maxOrder === null ? 0 : ((int) $maxOrder) + 1;

            return $restaurant->categories()->create($data)->refresh();
        });
    }

    public function update(Category $category, array $data): Category
    {
        $data = BilingualContent::synchronize($data);
        $category->update($data);

        return $category->refresh();
    }

    public function delete(Category $category): void
    {
        abort_if(
            $category->menuItems()->exists(),
            409,
            'Delete or move all menu items before deleting this category.',
        );

        DB::transaction(function () use ($category): void {
            $restaurantId = $category->restaurant_id;
            $category->delete();
            $this->compactOrdering($restaurantId);
        });
    }

    public function reorder(Restaurant $restaurant, array $categoryIds): Collection
    {
        return DB::transaction(function () use ($restaurant, $categoryIds): Collection {
            $ownedIds = $restaurant->categories()->lockForUpdate()->pluck('id')->all();
            $supplied = array_map('intval', $categoryIds);
            $expected = $ownedIds;
            sort($supplied);
            sort($expected);

            if ($supplied !== $expected) {
                throw ValidationException::withMessages([
                    'category_ids' => ['The list must contain every category belonging to your restaurant exactly once.'],
                ]);
            }

            foreach ($categoryIds as $sortOrder => $categoryId) {
                Category::where('restaurant_id', $restaurant->id)
                    ->whereKey($categoryId)
                    ->update(['sort_order' => $sortOrder]);
            }

            return $restaurant->categories()->ordered()->get();
        });
    }

    private function compactOrdering(int $restaurantId): void
    {
        Category::where('restaurant_id', $restaurantId)
            ->ordered()->pluck('id')
            ->each(fn (int $id, int $position) => Category::whereKey($id)->update(['sort_order' => $position]));
    }
}
