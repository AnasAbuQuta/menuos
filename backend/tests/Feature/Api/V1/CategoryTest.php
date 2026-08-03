<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_list_only_own_categories_in_order(): void
    {
        $restaurant = $this->actingOwner();
        $second = Category::factory()->for($restaurant)->create(['name' => 'Second', 'sort_order' => 1]);
        $first = Category::factory()->for($restaurant)->create(['name' => 'First', 'sort_order' => 0]);
        Category::factory()->create(['name' => 'Foreign']);

        $this->getJson('/api/v1/categories')->assertOk()
            ->assertJsonCount(2, 'data.categories')
            ->assertJsonPath('data.categories.0.id', $first->id)
            ->assertJsonPath('data.categories.1.id', $second->id)
            ->assertJsonMissing(['name' => 'Foreign']);
    }

    public function test_empty_category_list_is_returned(): void
    {
        $this->actingOwner();
        $this->getJson('/api/v1/categories')->assertOk()->assertJsonCount(0, 'data.categories');
    }

    public function test_category_can_be_created_and_name_is_normalized(): void
    {
        $restaurant = $this->actingOwner();

        $this->postJson('/api/v1/categories', ['name' => '  Starters  '])
            ->assertCreated()->assertJsonPath('data.category.name', 'Starters')
            ->assertJsonPath('data.category.sort_order', 0)
            ->assertJsonPath('data.category.is_active', true)
            ->assertJsonMissingPath('data.category.restaurant_id');

        $this->assertDatabaseHas('categories', ['restaurant_id' => $restaurant->id, 'name' => 'Starters']);
    }

    public function test_category_creation_validation_fails(): void
    {
        $restaurant = $this->actingOwner();

        $this->postJson('/api/v1/categories', [
            'name' => '   ', 'sort_order' => -1, 'restaurant_id' => $restaurant->id,
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'sort_order', 'restaurant_id']);
    }

    public function test_category_can_be_updated(): void
    {
        $restaurant = $this->actingOwner();
        $category = Category::factory()->for($restaurant)->create(['name' => 'Old']);

        $this->putJson("/api/v1/categories/{$category->id}", ['name' => '  New  '])
            ->assertOk()->assertJsonPath('data.category.name', 'New');
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'New']);
    }

    public function test_category_can_be_deleted(): void
    {
        $restaurant = $this->actingOwner();
        $category = Category::factory()->for($restaurant)->create();

        $this->deleteJson("/api/v1/categories/{$category->id}")->assertOk();
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_active_status_can_be_updated(): void
    {
        $restaurant = $this->actingOwner();
        $category = Category::factory()->for($restaurant)->create(['is_active' => true]);

        $this->putJson("/api/v1/categories/{$category->id}", ['is_active' => false])
            ->assertOk()->assertJsonPath('data.category.is_active', false);
    }

    public function test_direct_status_updates_preserve_bilingual_content(): void
    {
        $restaurant = $this->actingOwner();
        $category = Category::factory()->for($restaurant)->create([
            'name' => 'مشروبات', 'name_ar' => 'مشروبات', 'name_en' => 'Drinks', 'is_active' => true,
        ]);

        $this->putJson("/api/v1/categories/{$category->id}", ['is_active' => false])
            ->assertOk()->assertJsonPath('data.category.is_active', false);
        $this->putJson("/api/v1/categories/{$category->id}", ['is_active' => true])
            ->assertOk()->assertJsonPath('data.category.is_active', true);

        $category->refresh();
        $this->assertSame('مشروبات', $category->name_ar);
        $this->assertSame('Drinks', $category->name_en);
    }

    public function test_foreign_category_status_cannot_be_updated(): void
    {
        $this->actingOwner();
        $foreign = Category::factory()->create(['is_active' => true]);

        $this->putJson("/api/v1/categories/{$foreign->id}", ['is_active' => false])->assertNotFound();
        $this->assertTrue($foreign->fresh()->is_active);
    }

    public function test_ordered_listing_uses_id_as_tie_breaker(): void
    {
        $restaurant = $this->actingOwner();
        $first = Category::factory()->for($restaurant)->create(['sort_order' => 2]);
        $second = Category::factory()->for($restaurant)->create(['sort_order' => 2]);

        $this->getJson('/api/v1/categories')->assertJsonPath('data.categories.0.id', $first->id)
            ->assertJsonPath('data.categories.1.id', $second->id);
    }

    public function test_categories_can_be_reordered(): void
    {
        $restaurant = $this->actingOwner();
        $one = Category::factory()->for($restaurant)->create(['sort_order' => 0]);
        $two = Category::factory()->for($restaurant)->create(['sort_order' => 1]);
        $three = Category::factory()->for($restaurant)->create(['sort_order' => 2]);

        $this->postJson('/api/v1/categories/reorder', [
            'category_ids' => [$three->id, $one->id, $two->id],
        ])->assertOk()->assertJsonPath('data.categories.0.id', $three->id);

        $this->assertDatabaseHas('categories', ['id' => $three->id, 'sort_order' => 0]);
        $this->assertDatabaseHas('categories', ['id' => $one->id, 'sort_order' => 1]);
        $this->assertDatabaseHas('categories', ['id' => $two->id, 'sort_order' => 2]);
    }

    public function test_reorder_rejects_duplicate_ids_without_changes(): void
    {
        $restaurant = $this->actingOwner();
        $one = Category::factory()->for($restaurant)->create(['sort_order' => 0]);
        $two = Category::factory()->for($restaurant)->create(['sort_order' => 1]);

        $this->postJson('/api/v1/categories/reorder', [
            'category_ids' => [$one->id, $one->id],
        ])->assertUnprocessable()->assertJsonValidationErrors('category_ids.1');
        $this->assertSame(1, $two->fresh()->sort_order);
    }

    public function test_reorder_rejects_foreign_category_ids_without_changes(): void
    {
        $restaurant = $this->actingOwner();
        $owned = Category::factory()->for($restaurant)->create(['sort_order' => 0]);
        $foreign = Category::factory()->create();

        $this->postJson('/api/v1/categories/reorder', [
            'category_ids' => [$foreign->id],
        ])->assertUnprocessable()->assertJsonValidationErrors('category_ids');
        $this->assertSame(0, $owned->fresh()->sort_order);
    }

    public function test_reorder_rejects_missing_category_ids(): void
    {
        $restaurant = $this->actingOwner();
        $one = Category::factory()->for($restaurant)->create();
        Category::factory()->for($restaurant)->create();

        $this->postJson('/api/v1/categories/reorder', ['category_ids' => [$one->id]])
            ->assertUnprocessable()->assertJsonValidationErrors('category_ids');
    }

    public function test_user_without_restaurant_cannot_create_categories(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/categories', ['name' => 'Category'])
            ->assertStatus(409)->assertJsonPath('message', 'Create a restaurant before managing categories.');
    }

    public function test_user_cannot_view_another_restaurants_category(): void
    {
        $this->actingOwner();
        $foreign = Category::factory()->create();
        $this->getJson("/api/v1/categories/{$foreign->id}")->assertNotFound();
    }

    public function test_user_cannot_update_another_restaurants_category(): void
    {
        $this->actingOwner();
        $foreign = Category::factory()->create();
        $this->putJson("/api/v1/categories/{$foreign->id}", ['name' => 'Changed'])->assertNotFound();
        $this->assertNotSame('Changed', $foreign->fresh()->name);
    }

    public function test_user_cannot_delete_another_restaurants_category(): void
    {
        $this->actingOwner();
        $foreign = Category::factory()->create();
        $this->deleteJson("/api/v1/categories/{$foreign->id}")->assertNotFound();
        $this->assertModelExists($foreign);
    }

    public function test_unauthenticated_user_cannot_access_category_endpoints(): void
    {
        $this->getJson('/api/v1/categories')->assertUnauthorized();
        $this->postJson('/api/v1/categories', ['name' => 'Test'])->assertUnauthorized();
        $this->getJson('/api/v1/categories/1')->assertUnauthorized();
        $this->putJson('/api/v1/categories/1', ['name' => 'Test'])->assertUnauthorized();
        $this->deleteJson('/api/v1/categories/1')->assertUnauthorized();
        $this->postJson('/api/v1/categories/reorder', ['category_ids' => [1]])->assertUnauthorized();
    }

    private function actingOwner(): Restaurant
    {
        $user = User::factory()->create();
        $restaurant = Restaurant::factory()->for($user, 'owner')->create();
        Sanctum::actingAs($user);

        return $restaurant;
    }
}
