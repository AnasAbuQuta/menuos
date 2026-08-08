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

class AdminPlatformTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_routes_require_authentication_and_super_admin_access(): void
    {
        $this->getJson('/api/v1/admin/dashboard')->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/v1/admin/dashboard')->assertForbidden();
    }

    public function test_super_admin_dashboard_returns_platform_metrics_and_latest_records(): void
    {
        $admin = $this->admin();
        $restaurant = Restaurant::factory()->create();
        $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
        MenuItem::factory()->create(['restaurant_id' => $restaurant->id, 'category_id' => $category->id]);
        AnalyticsEvent::query()->create(['restaurant_id' => $restaurant->id, 'event_type' => 'menu_view', 'visitor_hash' => str_repeat('a', 64), 'occurred_at' => now()]);
        AnalyticsEvent::query()->create(['restaurant_id' => $restaurant->id, 'event_type' => 'whatsapp_click', 'visitor_hash' => str_repeat('b', 64), 'occurred_at' => now()]);

        Sanctum::actingAs($admin);
        $this->getJson('/api/v1/admin/dashboard')->assertOk()
            ->assertJsonPath('data.metrics.total_users', 2)
            ->assertJsonPath('data.metrics.total_restaurants', 1)
            ->assertJsonPath('data.metrics.total_categories', 1)
            ->assertJsonPath('data.metrics.total_menu_items', 1)
            ->assertJsonPath('data.metrics.total_public_menu_views', 1)
            ->assertJsonPath('data.metrics.total_whatsapp_clicks', 1)
            ->assertJsonCount(2, 'data.latest_users');
    }

    public function test_admin_can_search_filter_sort_and_paginate_users(): void
    {
        Sanctum::actingAs($this->admin());
        User::factory()->create(['name' => 'Active Owner', 'email' => 'active@example.com']);
        $disabled = User::factory()->create(['name' => 'Disabled Owner', 'email' => 'disabled@example.com']);
        $disabled->forceFill(['account_status' => 'disabled'])->save();

        $this->getJson('/api/v1/admin/users?search=Disabled&status=disabled&sort=name&per_page=1')
            ->assertOk()->assertJsonPath('data.users.0.email', 'disabled@example.com')
            ->assertJsonPath('data.meta.per_page', 1)->assertJsonPath('data.meta.total', 1);
    }

    public function test_admin_can_disable_and_reactivate_user_and_disabled_tokens_are_rejected(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['email' => 'owner@example.com']);
        $adminToken = $admin->createToken('admin-test')->plainTextToken;
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($adminToken)->patchJson("/api/v1/admin/users/{$user->id}/status", ['status' => 'disabled'])
            ->assertOk()->assertJsonPath('data.user.account_status', 'disabled');
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->postJson('/api/v1/auth/login', ['email' => 'owner@example.com', 'password' => 'password'])->assertUnprocessable();

        $this->withToken($adminToken)->patchJson("/api/v1/admin/users/{$user->id}/status", ['status' => 'active'])
            ->assertOk()->assertJsonPath('data.user.account_status', 'active');
    }

    public function test_super_admin_cannot_disable_self_or_final_active_admin(): void
    {
        $admin = $this->admin();
        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/admin/users/{$admin->id}/status", ['status' => 'disabled'])
            ->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'account_status' => 'active']);
    }

    public function test_admin_can_list_search_filter_suspend_and_reactivate_restaurants(): void
    {
        Sanctum::actingAs($this->admin());
        $restaurant = Restaurant::factory()->create(['name' => 'Platform Cafe', 'name_en' => 'Platform Cafe', 'slug' => 'platform-cafe']);

        $this->getJson('/api/v1/admin/restaurants?search=Platform&status=active&per_page=10')
            ->assertOk()->assertJsonPath('data.restaurants.0.slug', 'platform-cafe');

        $this->patchJson("/api/v1/admin/restaurants/{$restaurant->id}/status", ['status' => 'suspended'])
            ->assertOk()->assertJsonPath('data.restaurant.platform_status', 'suspended');
        $this->getJson('/api/v1/public/menu/platform-cafe')->assertNotFound();
        $this->postJson('/api/v1/public/menu/platform-cafe/analytics', [
            'visitor_id' => 'anonymous-visitor-1',
            'events' => [['type' => 'menu_view']],
        ])->assertNotFound();
        $this->get('/sitemap.xml')->assertOk()->assertDontSee('/menu/platform-cafe', false);

        $this->patchJson("/api/v1/admin/restaurants/{$restaurant->id}/status", ['status' => 'active'])
            ->assertOk()->assertJsonPath('data.restaurant.platform_status', 'active');
        $this->getJson('/api/v1/public/menu/platform-cafe')->assertOk();
    }

    public function test_admin_only_fields_cannot_be_mass_assigned(): void
    {
        $user = User::factory()->create();
        $restaurant = Restaurant::factory()->create();

        $user->fill(['is_super_admin' => true, 'account_status' => 'disabled'])->save();
        $restaurant->fill(['platform_status' => 'suspended'])->save();

        $this->assertFalse($user->refresh()->is_super_admin);
        $this->assertSame('active', $user->account_status);
        $this->assertSame('active', $restaurant->refresh()->platform_status);
    }

    public function test_disabled_user_existing_token_is_rejected_even_if_token_was_not_revoked_by_admin_endpoint(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('existing')->plainTextToken;
        $user->forceFill(['account_status' => 'disabled'])->save();

        $this->withToken($token)->getJson('/api/v1/auth/me')->assertForbidden();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_super_admin_commands_are_idempotent_and_last_admin_cannot_be_removed(): void
    {
        $user = User::factory()->create(['email' => 'admin@example.com']);
        $this->artisan('menuos:make-super-admin', ['email' => $user->email, '--force' => true])->assertSuccessful();
        $this->artisan('menuos:make-super-admin', ['email' => $user->email, '--force' => true])->assertSuccessful();
        $this->assertTrue($user->refresh()->isSuperAdmin());

        $this->artisan('menuos:remove-super-admin', ['email' => $user->email, '--force' => true])->assertFailed();
        $second = $this->admin('second@example.com');
        $this->artisan('menuos:remove-super-admin', ['email' => $user->email, '--force' => true])->assertSuccessful();
        $this->assertFalse($user->refresh()->isSuperAdmin());
        $this->assertTrue($second->refresh()->isSuperAdmin());
    }

    private function admin(string $email = 'platform@example.com'): User
    {
        $user = User::factory()->create(['email' => $email]);
        $user->forceFill(['is_super_admin' => true, 'account_status' => 'active'])->save();

        return $user->refresh();
    }
}
