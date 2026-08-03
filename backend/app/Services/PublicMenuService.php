<?php

namespace App\Services;

use App\Models\Restaurant;
use Carbon\CarbonImmutable;

class PublicMenuService
{
    public function findBySlug(string $slug, ?string $language = null): Restaurant
    {
        $restaurant = Restaurant::query()
            ->select([
                'id', 'name', 'name_ar', 'name_en', 'slug', 'description', 'description_ar', 'description_en', 'default_language', 'logo', 'cover_image',
                'whatsapp', 'phone', 'address', 'opening_hours', 'currency',
                'primary_color', 'is_active',
            ])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with(['categories' => function ($query): void {
                $query->select(['id', 'restaurant_id', 'name', 'name_ar', 'name_en', 'sort_order'])
                    ->where('is_active', true)
                    ->whereHas('menuItems', fn ($query) => $query->where('is_available', true))
                    ->orderBy('sort_order')->orderBy('id')
                    ->with(['menuItems' => function ($query): void {
                        $query->select([
                            'id', 'restaurant_id', 'category_id', 'name', 'name_ar', 'name_en', 'description', 'description_ar', 'description_en',
                            'price', 'image', 'is_featured', 'sort_order',
                        ])->where('is_available', true)->orderBy('sort_order')->orderBy('id');
                    }]);
            }])
            ->firstOrFail();

        $language ??= $restaurant->default_language;
        $restaurant->setAttribute('active_language', $language);
        $restaurant->categories->each(function ($category) use ($language): void {
            $category->setAttribute('active_language', $language);
            $category->menuItems->each(fn ($item) => $item->setAttribute('active_language', $language));
        });
        $restaurant->setAttribute('is_open_now', $this->isOpenNow($restaurant->opening_hours));

        return $restaurant;
    }

    private function isOpenNow(?array $openingHours): ?bool
    {
        if (! $openingHours) {
            return null;
        }

        $now = CarbonImmutable::now(config('app.timezone'));
        $day = strtolower($now->englishDayOfWeek);
        $schedule = $openingHours[$day] ?? null;

        if (! is_array($schedule) || ! array_key_exists('is_open', $schedule)) {
            return null;
        }
        if (! $schedule['is_open']) {
            return false;
        }
        if (! is_string($schedule['open'] ?? null) || ! is_string($schedule['close'] ?? null)) {
            return null;
        }
        if (! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $schedule['open']) ||
            ! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $schedule['close'])) {
            return null;
        }

        $open = CarbonImmutable::createFromFormat('H:i', $schedule['open'], config('app.timezone'));
        $close = CarbonImmutable::createFromFormat('H:i', $schedule['close'], config('app.timezone'));
        if ($close->lessThanOrEqualTo($open)) {
            return null;
        }

        $time = $now->format('H:i');

        return $time >= $schedule['open'] && $time < $schedule['close'];
    }
}
