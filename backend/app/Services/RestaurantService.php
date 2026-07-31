<?php

namespace App\Services;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
            if (isset($data['name']) && $data['name'] !== $restaurant->name) {
                $data['slug'] = $this->uniqueSlug($data['name'], $restaurant->id);
            }

            $restaurant->update($data);

            return $restaurant->refresh();
        });
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
