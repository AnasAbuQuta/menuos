<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MenuItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_menu_item_endpoints(): void
    {
        $this->getJson('/api/v1/menu-items')->assertUnauthorized();
        $this->postJson('/api/v1/menu-items', [])->assertUnauthorized();
        $this->getJson('/api/v1/menu-items/1')->assertUnauthorized();
        $this->putJson('/api/v1/menu-items/1', [])->assertUnauthorized();
        $this->deleteJson('/api/v1/menu-items/1')->assertUnauthorized();
        $this->postJson('/api/v1/menu-items/reorder', [])->assertUnauthorized();
    }

    public function test_user_without_restaurant_cannot_create_menu_items(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/v1/menu-items', $this->validPayload(1))
            ->assertStatus(409)->assertJsonPath('message', 'Create a restaurant before managing menu items.');
    }

    public function test_owner_lists_only_own_menu_items(): void
    {
        [$restaurant, $category] = $this->actingOwner();
        $own = $this->item($restaurant, $category, ['name' => 'Own item']);
        $foreign = MenuItem::factory()->create(['name' => 'Foreign item']);

        $this->getJson('/api/v1/menu-items')->assertOk()
            ->assertJsonCount(1, 'data.menu_items')
            ->assertJsonPath('data.menu_items.0.id', $own->id)
            ->assertJsonMissing(['id' => $foreign->id]);
    }

    public function test_owner_cannot_view_foreign_menu_item(): void
    {
        $this->actingOwner();
        $foreign = MenuItem::factory()->create();
        $this->getJson("/api/v1/menu-items/{$foreign->id}")->assertNotFound();
    }

    public function test_owner_cannot_update_foreign_menu_item(): void
    {
        $this->actingOwner();
        $foreign = MenuItem::factory()->create(['name' => 'Original']);
        $this->putJson("/api/v1/menu-items/{$foreign->id}", ['name' => 'Changed'])->assertNotFound();
        $this->assertSame('Original', $foreign->fresh()->name);
    }

    public function test_owner_cannot_delete_foreign_menu_item(): void
    {
        $this->actingOwner();
        $foreign = MenuItem::factory()->create();
        $this->deleteJson("/api/v1/menu-items/{$foreign->id}")->assertNotFound();
        $this->assertModelExists($foreign);
    }

    public function test_owner_cannot_assign_foreign_category(): void
    {
        $this->actingOwner();
        $foreignCategory = Category::factory()->create();
        $this->postJson('/api/v1/menu-items', $this->validPayload($foreignCategory->id))
            ->assertUnprocessable()->assertJsonValidationErrors('category_id');
    }

    public function test_valid_menu_item_creation_links_authenticated_restaurant(): void
    {
        [$restaurant, $category] = $this->actingOwner();
        $this->postJson('/api/v1/menu-items', $this->validPayload($category->id))
            ->assertCreated()->assertJsonPath('data.menu_item.name', 'Grilled Chicken')
            ->assertJsonPath('data.menu_item.price', '25.50')
            ->assertJsonMissingPath('data.menu_item.restaurant_id');

        $this->assertDatabaseHas('menu_items', [
            'restaurant_id' => $restaurant->id, 'category_id' => $category->id, 'name' => 'Grilled Chicken',
        ]);
    }

    public function test_creation_validation_failure(): void
    {
        [$restaurant, $category] = $this->actingOwner();
        $this->postJson('/api/v1/menu-items', [
            'category_id' => $category->id, 'name' => '', 'price' => 'abc', 'restaurant_id' => $restaurant->id,
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'price', 'restaurant_id']);
    }

    public function test_negative_and_excess_precision_prices_are_rejected(): void
    {
        [, $category] = $this->actingOwner();
        $this->postJson('/api/v1/menu-items', $this->validPayload($category->id, ['price' => '-1.00']))
            ->assertUnprocessable()->assertJsonValidationErrors('price');
        $this->postJson('/api/v1/menu-items', $this->validPayload($category->id, ['price' => '1.999']))
            ->assertUnprocessable()->assertJsonValidationErrors('price');
    }

    public function test_missing_category_is_rejected(): void
    {
        $this->actingOwner();
        $this->postJson('/api/v1/menu-items', $this->validPayload(999999))
            ->assertUnprocessable()->assertJsonValidationErrors('category_id');
    }

    public function test_whitespace_only_name_is_rejected(): void
    {
        [, $category] = $this->actingOwner();
        $this->postJson('/api/v1/menu-items', $this->validPayload($category->id, ['name' => '   ']))
            ->assertUnprocessable()->assertJsonValidationErrors('name');
    }

    public function test_optional_fields_use_correct_defaults(): void
    {
        [, $category] = $this->actingOwner();
        $response = $this->postJson('/api/v1/menu-items', [
            'category_id' => $category->id, 'name' => 'Water', 'price' => '0',
        ])->assertCreated();
        $response->assertJsonPath('data.menu_item.is_available', true)
            ->assertJsonPath('data.menu_item.is_featured', false)
            ->assertJsonPath('data.menu_item.sort_order', 0)
            ->assertJsonPath('data.menu_item.image_url', null);
    }

    public function test_valid_image_upload_succeeds_and_returns_url(): void
    {
        Storage::fake('public');
        [$restaurant, $category] = $this->actingOwner();
        $response = $this->post('/api/v1/menu-items', [
            ...$this->validPayload($category->id),
            'image' => UploadedFile::fake()->image('meal.webp'),
        ], ['Accept' => 'application/json'])->assertCreated();

        $item = MenuItem::firstOrFail();
        Storage::disk('public')->assertExists($item->image);
        $this->assertStringStartsWith("menu-items/{$restaurant->id}/", $item->image);
        $this->assertStringContainsString('/storage/menu-items/', $response->json('data.menu_item.image_url'));
    }

    public function test_invalid_file_type_is_rejected(): void
    {
        Storage::fake('public');
        [, $category] = $this->actingOwner();
        $this->post('/api/v1/menu-items', [
            ...$this->validPayload($category->id),
            'image' => UploadedFile::fake()->createWithContent('payload.php', '<?php echo 1;'),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('image');
    }

    public function test_oversized_image_is_rejected(): void
    {
        Storage::fake('public');
        [, $category] = $this->actingOwner();
        $this->post('/api/v1/menu-items', [
            ...$this->validPayload($category->id),
            'image' => UploadedFile::fake()->image('large.jpg')->size(2049),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('image');
    }

    public function test_replacing_image_removes_previous_image(): void
    {
        Storage::fake('public');
        [$restaurant, $category] = $this->actingOwner();
        $oldPath = "menu-items/{$restaurant->id}/old.jpg";
        Storage::disk('public')->put($oldPath, 'old');
        $item = $this->item($restaurant, $category, ['image' => $oldPath]);

        $this->post("/api/v1/menu-items/{$item->id}", [
            '_method' => 'PUT', 'image' => UploadedFile::fake()->image('new.png'),
        ], ['Accept' => 'application/json'])->assertOk();

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($item->fresh()->image);
    }

    public function test_deleting_item_removes_stored_image(): void
    {
        Storage::fake('public');
        [$restaurant, $category] = $this->actingOwner();
        $path = "menu-items/{$restaurant->id}/item.jpg";
        Storage::disk('public')->put($path, 'image');
        $item = $this->item($restaurant, $category, ['image' => $path]);

        $this->deleteJson("/api/v1/menu-items/{$item->id}")->assertOk();
        Storage::disk('public')->assertMissing($path);
    }

    public function test_empty_item_list_works(): void
    {
        $this->actingOwner();
        $this->getJson('/api/v1/menu-items')->assertOk()->assertJsonCount(0, 'data.menu_items');
    }

    public function test_items_are_ordered_by_category_then_item_order_then_id(): void
    {
        [$restaurant, $lateCategory] = $this->actingOwner();
        $lateCategory->update(['sort_order' => 5]);
        $earlyCategory = Category::factory()->for($restaurant)->create(['sort_order' => 0]);
        $late = $this->item($restaurant, $lateCategory, ['sort_order' => 0]);
        $second = $this->item($restaurant, $earlyCategory, ['sort_order' => 2]);
        $first = $this->item($restaurant, $earlyCategory, ['sort_order' => 1]);

        $this->getJson('/api/v1/menu-items')->assertJsonPath('data.menu_items.0.id', $first->id)
            ->assertJsonPath('data.menu_items.1.id', $second->id)
            ->assertJsonPath('data.menu_items.2.id', $late->id);
    }

    public function test_category_filter_is_scoped_and_works(): void
    {
        [$restaurant, $category] = $this->actingOwner();
        $other = Category::factory()->for($restaurant)->create();
        $wanted = $this->item($restaurant, $category);
        $this->item($restaurant, $other);
        $this->getJson("/api/v1/menu-items?category_id={$category->id}")
            ->assertOk()->assertJsonCount(1, 'data.menu_items')->assertJsonPath('data.menu_items.0.id', $wanted->id);

        $foreign = Category::factory()->create();
        $this->getJson("/api/v1/menu-items?category_id={$foreign->id}")
            ->assertUnprocessable()->assertJsonValidationErrors('category_id');
    }

    public function test_availability_and_featured_filters_work(): void
    {
        [$restaurant, $category] = $this->actingOwner();
        $wanted = $this->item($restaurant, $category, ['is_available' => false, 'is_featured' => true]);
        $this->item($restaurant, $category, ['is_available' => true, 'is_featured' => false]);
        $this->getJson('/api/v1/menu-items?is_available=0&is_featured=1')
            ->assertOk()->assertJsonCount(1, 'data.menu_items')->assertJsonPath('data.menu_items.0.id', $wanted->id);
    }

    public function test_search_matches_name_and_description(): void
    {
        [$restaurant, $category] = $this->actingOwner();
        $byName = $this->item($restaurant, $category, ['name' => 'Truffle Burger', 'description' => null]);
        $byDescription = $this->item($restaurant, $category, ['name' => 'Soup', 'description' => 'Fresh truffle cream']);
        $this->item($restaurant, $category, ['name' => 'Water', 'description' => 'Cold']);

        $this->getJson('/api/v1/menu-items?search=truffle')->assertOk()
            ->assertJsonCount(2, 'data.menu_items')
            ->assertJsonFragment(['id' => $byName->id])->assertJsonFragment(['id' => $byDescription->id]);
    }

    public function test_menu_item_update_and_toggles_succeed(): void
    {
        [$restaurant, $category] = $this->actingOwner();
        $item = $this->item($restaurant, $category);
        $this->putJson("/api/v1/menu-items/{$item->id}", [
            'name' => 'Updated', 'price' => '19.95', 'is_available' => false, 'is_featured' => true,
        ])->assertOk()->assertJsonPath('data.menu_item.name', 'Updated')
            ->assertJsonPath('data.menu_item.is_available', false)->assertJsonPath('data.menu_item.is_featured', true);
    }

    public function test_category_reassignment_to_owned_category_succeeds(): void
    {
        [$restaurant, $category] = $this->actingOwner();
        $newCategory = Category::factory()->for($restaurant)->create();
        $item = $this->item($restaurant, $category);
        $this->putJson("/api/v1/menu-items/{$item->id}", ['category_id' => $newCategory->id])
            ->assertOk()->assertJsonPath('data.menu_item.category.id', $newCategory->id);
    }

    public function test_menu_item_deletion_succeeds(): void
    {
        [$restaurant, $category] = $this->actingOwner();
        $item = $this->item($restaurant, $category);
        $this->deleteJson("/api/v1/menu-items/{$item->id}")->assertOk();
        $this->assertModelMissing($item);
    }

    public function test_reordering_succeeds_with_contiguous_values(): void
    {
        [$restaurant, $category] = $this->actingOwner();
        $one = $this->item($restaurant, $category, ['sort_order' => 0]);
        $two = $this->item($restaurant, $category, ['sort_order' => 1]);
        $this->postJson('/api/v1/menu-items/reorder', [
            'category_id' => $category->id, 'menu_item_ids' => [$two->id, $one->id],
        ])->assertOk()->assertJsonPath('data.menu_items.0.id', $two->id);
        $this->assertSame(0, $two->fresh()->sort_order);
        $this->assertSame(1, $one->fresh()->sort_order);
    }

    public function test_reorder_rejects_duplicates_missing_foreign_and_other_category_ids_atomically(): void
    {
        [$restaurant, $category] = $this->actingOwner();
        $one = $this->item($restaurant, $category, ['sort_order' => 0]);
        $two = $this->item($restaurant, $category, ['sort_order' => 1]);
        $otherCategory = Category::factory()->for($restaurant)->create();
        $otherCategoryItem = $this->item($restaurant, $otherCategory);
        $foreign = MenuItem::factory()->create();

        $this->postJson('/api/v1/menu-items/reorder', ['category_id' => $category->id, 'menu_item_ids' => [$one->id, $one->id]])
            ->assertUnprocessable();
        $this->postJson('/api/v1/menu-items/reorder', ['category_id' => $category->id, 'menu_item_ids' => [$one->id]])
            ->assertUnprocessable()->assertJsonValidationErrors('menu_item_ids');
        $this->postJson('/api/v1/menu-items/reorder', ['category_id' => $category->id, 'menu_item_ids' => [$one->id, $foreign->id]])
            ->assertUnprocessable()->assertJsonValidationErrors('menu_item_ids');
        $this->postJson('/api/v1/menu-items/reorder', ['category_id' => $category->id, 'menu_item_ids' => [$one->id, $otherCategoryItem->id]])
            ->assertUnprocessable()->assertJsonValidationErrors('menu_item_ids');
        $this->assertSame(0, $one->fresh()->sort_order);
        $this->assertSame(1, $two->fresh()->sort_order);
    }

    public function test_empty_category_can_be_deleted(): void
    {
        [, $category] = $this->actingOwner();
        $this->deleteJson("/api/v1/categories/{$category->id}")->assertOk();
        $this->assertModelMissing($category);
    }

    public function test_category_with_items_cannot_be_deleted_and_data_is_preserved(): void
    {
        [$restaurant, $category] = $this->actingOwner();
        $item = $this->item($restaurant, $category);
        $this->deleteJson("/api/v1/categories/{$category->id}")->assertStatus(409)
            ->assertJsonPath('message', 'Delete or move all menu items before deleting this category.');
        $this->assertModelExists($category);
        $this->assertModelExists($item);
    }

    private function actingOwner(): array
    {
        $user = User::factory()->create();
        $restaurant = Restaurant::factory()->for($user, 'owner')->create();
        $category = Category::factory()->for($restaurant)->create();
        Sanctum::actingAs($user);

        return [$restaurant, $category];
    }

    private function item(Restaurant $restaurant, Category $category, array $attributes = []): MenuItem
    {
        return MenuItem::factory()->create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $category->id,
            ...$attributes,
        ]);
    }

    private function validPayload(int $categoryId, array $overrides = []): array
    {
        return [
            'category_id' => $categoryId,
            'name' => 'Grilled Chicken',
            'description' => 'Served with vegetables.',
            'price' => '25.50',
            ...$overrides,
        ];
    }
}
