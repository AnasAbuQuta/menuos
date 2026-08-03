<?php

namespace Tests\Feature\Api\V1;

use App\Models\AnalyticsEvent;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_events_are_batched_and_visitor_identifier_is_irreversibly_hashed(): void
    {
        $restaurant = Restaurant::factory()->create(['slug' => 'analytics-cafe']);
        $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
        $item = MenuItem::factory()->create(['restaurant_id' => $restaurant->id, 'category_id' => $category->id]);

        $this->postJson('/api/v1/public/menu/analytics-cafe/analytics', [
            'visitor_id' => 'anonymous-browser-id-12345',
            'events' => [['type' => 'menu_view', 'source' => 'qr'], ['type' => 'category_click', 'subject_id' => $category->id], ['type' => 'item_click', 'subject_id' => $item->id]],
        ])->assertAccepted();

        $this->assertDatabaseCount('analytics_events', 3);
        $event = AnalyticsEvent::query()->firstOrFail();
        $this->assertNotSame('anonymous-browser-id-12345', $event->visitor_hash);
        $this->assertSame(64, strlen($event->visitor_hash));
    }

    public function test_invalid_subjects_from_another_tenant_are_not_recorded(): void
    {
        $restaurant = Restaurant::factory()->create(['slug' => 'mine']);
        $other = Restaurant::factory()->create();
        $category = Category::factory()->create(['restaurant_id' => $other->id]);
        $item = MenuItem::factory()->create(['restaurant_id' => $other->id, 'category_id' => $category->id]);

        $this->postJson('/api/v1/public/menu/mine/analytics', ['visitor_id' => 'anonymous-browser-id-12345', 'events' => [['type' => 'item_click', 'subject_id' => $item->id]]])->assertAccepted();
        $this->assertDatabaseCount('analytics_events', 0);
    }

    public function test_owner_receives_only_their_aggregate_analytics(): void
    {
        $user = User::factory()->create();
        $restaurant = Restaurant::factory()->for($user, 'owner')->create();
        $other = Restaurant::factory()->create();
        AnalyticsEvent::query()->create(['restaurant_id' => $restaurant->id, 'event_type' => 'menu_view', 'visitor_hash' => str_repeat('a', 64), 'source' => 'direct', 'occurred_at' => now()]);
        AnalyticsEvent::query()->create(['restaurant_id' => $other->id, 'event_type' => 'menu_view', 'visitor_hash' => str_repeat('b', 64), 'source' => 'social', 'occurred_at' => now()]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/restaurant/analytics')->assertOk()->assertJsonPath('data.analytics.summary.views', 1)->assertJsonPath('data.analytics.summary.visitors', 1)->assertJsonMissing(['social']);
    }

    public function test_analytics_validation_rate_limit_and_security_headers_are_present(): void
    {
        Restaurant::factory()->create(['slug' => 'secure-menu']);
        $this->postJson('/api/v1/public/menu/secure-menu/analytics', ['visitor_id' => 'short', 'events' => []])->assertUnprocessable()->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'DENY');
        $route = collect(app('router')->getRoutes())->first(fn ($route) => $route->uri() === 'api/v1/public/menu/{slug}/analytics');
        $this->assertContains('throttle:public-analytics', $route->gatherMiddleware());
    }
}
