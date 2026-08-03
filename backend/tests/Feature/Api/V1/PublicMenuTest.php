<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Restaurant;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicMenuTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_public_menu_is_available_without_authentication_and_exposes_only_safe_fields(): void
    {
        $restaurant = Restaurant::factory()->create([
            'slug' => 'green-table', 'logo' => 'restaurants/1/logo/logo.jpg',
            'cover_image' => 'restaurants/1/cover/cover.jpg', 'primary_color' => '#176B52', 'theme_key' => 'cafe',
        ]);
        $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
        MenuItem::factory()->create([
            'restaurant_id' => $restaurant->id, 'category_id' => $category->id,
            'image' => 'menu-items/1/dish.jpg', 'is_featured' => true,
        ]);

        $this->getJson('/api/v1/public/menu/green-table')
            ->assertOk()
            ->assertJsonPath('data.menu.slug', 'green-table')
            ->assertJsonPath('data.menu.theme_key', 'cafe')
            ->assertJsonPath('data.menu.categories.0.menu_items.0.is_featured', true)
            ->assertJsonStructure(['data' => ['menu' => ['logo_url', 'cover_image_url', 'categories']]])
            ->assertJsonMissingPath('data.menu.owner_id')
            ->assertJsonMissingPath('data.menu.categories.0.restaurant_id')
            ->assertJsonMissingPath('data.menu.categories.0.menu_items.0.restaurant_id')
            ->assertJsonMissingPath('data.menu.categories.0.menu_items.0.is_available');
    }

    public function test_unknown_inactive_and_malformed_restaurants_are_not_public(): void
    {
        Restaurant::factory()->create(['slug' => 'closed-place', 'is_active' => false]);

        $this->getJson('/api/v1/public/menu/missing')->assertNotFound();
        $this->getJson('/api/v1/public/menu/closed-place')->assertNotFound();
        $this->getJson('/api/v1/public/menu/INVALID_SLUG')->assertNotFound();
    }

    public function test_only_active_non_empty_categories_and_available_items_are_returned_in_order(): void
    {
        $restaurant = Restaurant::factory()->create(['slug' => 'ordered-menu']);
        $second = Category::factory()->create(['restaurant_id' => $restaurant->id, 'name' => 'Second', 'sort_order' => 20]);
        $first = Category::factory()->create(['restaurant_id' => $restaurant->id, 'name' => 'First', 'sort_order' => 10]);
        $inactive = Category::factory()->create(['restaurant_id' => $restaurant->id, 'is_active' => false]);
        Category::factory()->create(['restaurant_id' => $restaurant->id, 'name' => 'Empty']);
        MenuItem::factory()->create(['restaurant_id' => $restaurant->id, 'category_id' => $second->id, 'name' => 'Later']);
        MenuItem::factory()->create(['restaurant_id' => $restaurant->id, 'category_id' => $first->id, 'name' => 'B', 'sort_order' => 2]);
        MenuItem::factory()->create(['restaurant_id' => $restaurant->id, 'category_id' => $first->id, 'name' => 'A', 'sort_order' => 1]);
        MenuItem::factory()->create(['restaurant_id' => $restaurant->id, 'category_id' => $first->id, 'name' => 'Hidden', 'is_available' => false]);
        MenuItem::factory()->create(['restaurant_id' => $restaurant->id, 'category_id' => $inactive->id, 'name' => 'Inactive category item']);

        $response = $this->getJson('/api/v1/public/menu/ordered-menu')->assertOk();
        $this->assertSame(['First', 'Second'], array_column($response->json('data.menu.categories'), 'name'));
        $this->assertSame(['A', 'B'], array_column($response->json('data.menu.categories.0.menu_items'), 'name'));
    }

    public function test_menu_never_leaks_items_from_another_restaurant(): void
    {
        $restaurant = Restaurant::factory()->create(['slug' => 'tenant-one']);
        $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
        MenuItem::factory()->create(['restaurant_id' => $restaurant->id, 'category_id' => $category->id, 'name' => 'Mine']);
        $other = Restaurant::factory()->create();
        $otherCategory = Category::factory()->create(['restaurant_id' => $other->id]);
        MenuItem::factory()->create(['restaurant_id' => $other->id, 'category_id' => $otherCategory->id, 'name' => 'Not mine']);

        $this->getJson('/api/v1/public/menu/tenant-one')->assertOk()
            ->assertJsonFragment(['name' => 'Mine'])
            ->assertJsonMissing(['name' => 'Not mine']);
    }

    public function test_open_status_is_computed_in_the_application_timezone(): void
    {
        config(['app.timezone' => 'Asia/Riyadh']);
        Carbon::setTestNow(Carbon::parse('2026-08-03 12:00:00', 'Asia/Riyadh'));
        $hours = ['monday' => ['is_open' => true, 'open' => '09:00', 'close' => '17:00']];
        $open = Restaurant::factory()->create(['slug' => 'open-now', 'opening_hours' => $hours]);
        $closed = Restaurant::factory()->create(['slug' => 'closed-now', 'opening_hours' => ['monday' => ['is_open' => false, 'open' => null, 'close' => null]]]);
        $unknown = Restaurant::factory()->create(['slug' => 'unknown-hours', 'opening_hours' => null]);

        $this->getJson("/api/v1/public/menu/{$open->slug}")->assertJsonPath('data.menu.is_open_now', true);
        $this->getJson("/api/v1/public/menu/{$closed->slug}")->assertJsonPath('data.menu.is_open_now', false);
        $this->getJson("/api/v1/public/menu/{$unknown->slug}")->assertJsonPath('data.menu.is_open_now', null);
    }

    public function test_public_menu_route_is_rate_limited(): void
    {
        $route = collect(app('router')->getRoutes())->first(fn ($route) => $route->uri() === 'api/v1/public/menu/{slug}');

        $this->assertNotNull($route);
        $this->assertContains('throttle:public-menu', $route->gatherMiddleware());
    }
}
