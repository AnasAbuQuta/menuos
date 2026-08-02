<?php

namespace App\Services;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class RestaurantService
{
    public function create(User $owner, array $data): Restaurant
    {
        return DB::transaction(function () use ($owner, $data): Restaurant {
            $data['owner_id'] = $owner->id;
            $data['slug'] = $this->uniqueSlug($data['name']);

            return Restaurant::create($data)->refresh();
        });
    }

    public function update(Restaurant $restaurant, array $data): Restaurant
    {
        return DB::transaction(function () use ($restaurant, $data): Restaurant {
            $restaurant->update($data);

            return $restaurant->refresh();
        });
    }

    public function uploadImage(Restaurant $restaurant, UploadedFile $image, string $field, string $directory): Restaurant
    {
        $oldPath = $restaurant->{$field};
        $newPath = $image->store("restaurants/{$restaurant->id}/{$directory}", 'public');

        try {
            DB::transaction(function () use ($restaurant, $field, $newPath): void {
                $restaurant->update([$field => $newPath]);
            });
        } catch (Throwable $exception) {
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }
            throw $exception;
        }

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return $restaurant->refresh();
    }

    public function deleteImage(Restaurant $restaurant, string $field): Restaurant
    {
        $path = $restaurant->{$field};

        DB::transaction(function () use ($restaurant, $field): void {
            $restaurant->update([$field => null]);
        });

        if ($path) {
            Storage::disk('public')->delete($path);
        }

        return $restaurant->refresh();
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $base = $base !== '' ? $base : 'restaurant-'.Str::lower(Str::random(8));
        $slug = $base;
        $suffix = 2;

        while (Restaurant::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
