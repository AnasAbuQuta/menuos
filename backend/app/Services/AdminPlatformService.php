<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminPlatformService
{
    public function dashboard(): array
    {
        $eventCount = fn (string $type): int => AnalyticsEvent::query()->where('event_type', $type)->count();

        return [
            'metrics' => [
                'total_users' => User::query()->count(),
                'active_users' => User::query()->where('account_status', 'active')->count(),
                'disabled_users' => User::query()->where('account_status', 'disabled')->count(),
                'total_restaurants' => Restaurant::query()->count(),
                'active_restaurants' => Restaurant::query()->where('is_active', true)->where('platform_status', 'active')->count(),
                'inactive_restaurants' => Restaurant::query()->where('is_active', false)->where('platform_status', 'active')->count(),
                'suspended_restaurants' => Restaurant::query()->where('platform_status', 'suspended')->count(),
                'total_categories' => Category::query()->count(),
                'total_menu_items' => MenuItem::query()->count(),
                'total_public_menu_views' => $eventCount('menu_view'),
                'total_whatsapp_clicks' => $eventCount('whatsapp_click'),
                'total_phone_clicks' => $eventCount('phone_click'),
                'total_qr_visits' => $eventCount('qr_visit'),
                'new_users_last_7_days' => User::query()->where('created_at', '>=', now()->subDays(7))->count(),
                'new_restaurants_last_7_days' => Restaurant::query()->where('created_at', '>=', now()->subDays(7))->count(),
            ],
            'latest_users' => User::query()->with('restaurant')->latest('id')->limit(5)->get(),
            'latest_restaurants' => $this->restaurantQuery()->latest('restaurants.id')->limit(5)->get(),
        ];
    }

    public function users(array $filters): LengthAwarePaginator
    {
        return User::query()->with('restaurant')
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(fn (Builder $query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('account_status', $status))
            ->tap(fn (Builder $query) => $this->sort($query, $filters['sort'] ?? 'newest', 'users'))
            ->paginate($filters['per_page'] ?? 20)->withQueryString();
    }

    public function user(User $user): User
    {
        return $user->load('restaurant');
    }

    public function updateUserStatus(User $actor, User $user, string $status): User
    {
        if ($actor->is($user) && $status === 'disabled') {
            throw ValidationException::withMessages(['status' => ['You cannot disable your own account.']]);
        }
        if ($user->isSuperAdmin() && $status === 'disabled' && User::query()->where('is_super_admin', true)->where('account_status', 'active')->count() <= 1) {
            throw ValidationException::withMessages(['status' => ['The final active Super Admin cannot be disabled.']]);
        }

        return DB::transaction(function () use ($user, $status): User {
            $user->forceFill(['account_status' => $status])->save();
            if ($status === 'disabled') {
                $user->tokens()->delete();
            }

            return $user->refresh()->load('restaurant');
        });
    }

    public function restaurants(array $filters): LengthAwarePaginator
    {
        return $this->restaurantQuery()
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(function (Builder $query) use ($search): void {
                $query->where('restaurants.name', 'like', "%{$search}%")->orWhere('restaurants.name_ar', 'like', "%{$search}%")->orWhere('restaurants.name_en', 'like', "%{$search}%")->orWhere('restaurants.slug', 'like', "%{$search}%")
                    ->orWhereHas('owner', fn (Builder $query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            }))
            ->when($filters['status'] ?? null, function (Builder $query, string $status): void {
                if ($status === 'suspended') {
                    $query->where('platform_status', 'suspended');
                } else {
                    $query->where('platform_status', 'active')->where('is_active', $status === 'active');
                }
            })
            ->tap(fn (Builder $query) => $this->sort($query, $filters['sort'] ?? 'newest', 'restaurants'))
            ->paginate($filters['per_page'] ?? 20)->withQueryString();
    }

    public function restaurant(Restaurant $restaurant): Restaurant
    {
        return $this->restaurantQuery()->findOrFail($restaurant->id);
    }

    public function updateRestaurantStatus(Restaurant $restaurant, string $status): Restaurant
    {
        return DB::transaction(function () use ($restaurant, $status): Restaurant {
            $restaurant->forceFill(['platform_status' => $status])->save();

            return $this->restaurant($restaurant->refresh());
        });
    }

    private function restaurantQuery(): Builder
    {
        return Restaurant::query()->with('owner')->withCount(['categories', 'menuItems'])
            ->withCount([
                'analyticsEvents as public_menu_views' => fn (Builder $query) => $query->where('event_type', 'menu_view'),
                'analyticsEvents as qr_visits' => fn (Builder $query) => $query->where('event_type', 'qr_visit'),
                'analyticsEvents as whatsapp_clicks' => fn (Builder $query) => $query->where('event_type', 'whatsapp_click'),
                'analyticsEvents as phone_clicks' => fn (Builder $query) => $query->where('event_type', 'phone_click'),
            ]);
    }

    private function sort(Builder $query, string $sort, string $table): void
    {
        match ($sort) {
            'oldest' => $query->orderBy("{$table}.created_at")->orderBy("{$table}.id"),
            'name' => $query->orderBy("{$table}.name")->orderBy("{$table}.id"),
            default => $query->latest("{$table}.created_at")->latest("{$table}.id"),
        };
    }
}
