<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use App\Models\Restaurant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function record(Restaurant $restaurant, string $visitorId, array $events): void
    {
        $visitorHash = hash_hmac('sha256', $visitorId, (string) config('app.key'));
        $itemIds = $restaurant->menuItems()->pluck('id')->all();
        $categoryIds = $restaurant->categories()->pluck('id')->all();
        $now = now();

        $rows = collect($events)->map(function (array $event) use ($restaurant, $visitorHash, $itemIds, $categoryIds, $now): ?array {
            $subjectType = match ($event['type']) {
                'item_click' => 'item', 'category_click' => 'category', default => null
            };
            $subjectId = $event['subject_id'] ?? null;
            if (($subjectType === 'item' && ! in_array($subjectId, $itemIds, true)) || ($subjectType === 'category' && ! in_array($subjectId, $categoryIds, true))) {
                return null;
            }

            return ['restaurant_id' => $restaurant->id, 'event_type' => $event['type'], 'visitor_hash' => $visitorHash, 'subject_type' => $subjectType, 'subject_id' => $subjectType ? $subjectId : null, 'source' => $event['source'] ?? null, 'occurred_at' => $event['occurred_at'] ?? $now, 'created_at' => $now, 'updated_at' => $now];
        })->filter()->all();

        if ($rows) {
            AnalyticsEvent::query()->insert($rows);
        }
    }

    public function dashboard(Restaurant $restaurant): array
    {
        $now = CarbonImmutable::now();
        $base = AnalyticsEvent::query()->where('restaurant_id', $restaurant->id);
        $counts = fn (string $type, ?CarbonImmutable $from = null) => (clone $base)->where('event_type', $type)->when($from, fn ($query) => $query->where('occurred_at', '>=', $from))->count();
        $daily = $this->dailySeries($restaurant->id, 30);

        return [
            'summary' => ['views' => $counts('menu_view'), 'visitors' => (clone $base)->where('event_type', 'menu_view')->distinct('visitor_hash')->count('visitor_hash'), 'qr_visits' => $counts('qr_visit'), 'whatsapp_clicks' => $counts('whatsapp_click'), 'phone_clicks' => $counts('phone_click'), 'daily_views' => $counts('menu_view', $now->startOfDay()), 'weekly_views' => $counts('menu_view', $now->subDays(6)->startOfDay()), 'monthly_views' => $counts('menu_view', $now->subDays(29)->startOfDay())],
            'views_7_days' => array_slice($daily, -7), 'views_30_days' => $daily,
            'top_items' => $this->topSubjects($restaurant, 'item'), 'top_categories' => $this->topSubjects($restaurant, 'category'),
            'traffic_sources' => (clone $base)->where('event_type', 'menu_view')->selectRaw("COALESCE(source, 'direct') as label, COUNT(*) as value")->groupBy('source')->orderByDesc('value')->limit(5)->get(),
            'recent_activity' => (clone $base)->latest('occurred_at')->limit(8)->get(['event_type', 'subject_type', 'subject_id', 'source', 'occurred_at']),
        ];
    }

    private function dailySeries(int $restaurantId, int $days): array
    {
        $start = CarbonImmutable::now()->subDays($days - 1)->startOfDay();
        $values = AnalyticsEvent::query()->where('restaurant_id', $restaurantId)->where('event_type', 'menu_view')->where('occurred_at', '>=', $start)->get()->countBy(fn ($event) => $event->occurred_at->format('Y-m-d'));

        return collect(range(0, $days - 1))->map(fn ($offset) => ['label' => $start->addDays($offset)->format('M j'), 'date' => $start->addDays($offset)->toDateString(), 'value' => $values[$start->addDays($offset)->toDateString()] ?? 0])->all();
    }

    private function topSubjects(Restaurant $restaurant, string $type): Collection
    {
        $names = $type === 'item' ? $restaurant->menuItems()->pluck('name', 'id') : $restaurant->categories()->pluck('name', 'id');

        return AnalyticsEvent::query()->where('restaurant_id', $restaurant->id)->where('subject_type', $type)->select('subject_id', DB::raw('COUNT(*) as value'))->groupBy('subject_id')->orderByDesc('value')->limit(5)->get()->map(fn ($row) => ['id' => $row->subject_id, 'label' => $names[$row->subject_id] ?? ucfirst($type), 'value' => (int) $row->value]);
    }
}
