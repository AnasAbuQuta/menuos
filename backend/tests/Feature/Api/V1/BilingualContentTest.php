<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BilingualContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_content_remains_available_through_localized_fallback(): void
    {
        $restaurant = Restaurant::factory()->create(['name' => 'Legacy restaurant', 'name_ar' => null, 'name_en' => null]);
        $category = Category::factory()->create(['restaurant_id' => $restaurant->id, 'name' => 'Legacy category', 'name_ar' => null]);
        $item = MenuItem::factory()->create(['restaurant_id' => $restaurant->id, 'category_id' => $category->id, 'name' => 'Legacy item', 'description' => 'Legacy description', 'name_ar' => null, 'description_ar' => null]);

        $this->assertSame('Legacy restaurant', $restaurant->getLocalizedName('ar'));
        $this->assertSame('Legacy category', $category->getLocalizedName('en'));
        $this->assertSame('Legacy item', $item->getLocalizedName('ar'));
        $this->assertSame('Legacy description', $item->getLocalizedDescription('en'));
    }

    public function test_restaurant_accepts_arabic_or_english_only_and_rejects_missing_names(): void
    {
        $arabicOwner = User::factory()->create();
        Sanctum::actingAs($arabicOwner);
        $this->postJson('/api/v1/restaurant', ['name_ar' => 'مطعم عربي'])
            ->assertCreated()->assertJsonPath('data.restaurant.name_ar', 'مطعم عربي');

        $englishOwner = User::factory()->create();
        Sanctum::actingAs($englishOwner);
        $this->postJson('/api/v1/restaurant', ['name_en' => 'English Restaurant', 'default_language' => 'en'])
            ->assertCreated()->assertJsonPath('data.restaurant.name_en', 'English Restaurant');

        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/v1/restaurant', ['name_ar' => '   ', 'name_en' => ''])
            ->assertUnprocessable()->assertJsonValidationErrors(['name_ar', 'name_en']);
    }

    public function test_invalid_default_language_is_rejected(): void
    {
        $restaurant = $this->actingOwner();
        $this->putJson('/api/v1/restaurant', ['default_language' => 'fr'])
            ->assertUnprocessable()->assertJsonValidationErrors('default_language');
        $this->assertSame('ar', $restaurant->fresh()->default_language);
    }

    public function test_categories_accept_either_language_name(): void
    {
        $this->actingOwner();
        $this->postJson('/api/v1/categories', ['name_ar' => 'مشروبات'])->assertCreated()->assertJsonPath('data.category.name_ar', 'مشروبات');
        $this->postJson('/api/v1/categories', ['name_en' => 'Desserts'])->assertCreated()->assertJsonPath('data.category.name_en', 'Desserts');
        $this->postJson('/api/v1/categories', ['name_ar' => ' ', 'name_en' => ' '])->assertUnprocessable();
    }

    public function test_menu_items_accept_either_language_name(): void
    {
        $restaurant = $this->actingOwner();
        $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
        $base = ['category_id' => $category->id, 'price' => '10.00'];
        $this->postJson('/api/v1/menu-items', $base + ['name_ar' => 'شاورما'])->assertCreated()->assertJsonPath('data.menu_item.name_ar', 'شاورما');
        $this->postJson('/api/v1/menu-items', $base + ['name_en' => 'Burger'])->assertCreated()->assertJsonPath('data.menu_item.name_en', 'Burger');
    }

    public function test_public_menu_defaults_to_restaurant_language_and_supports_explicit_languages(): void
    {
        $restaurant = Restaurant::factory()->create([
            'slug' => 'bilingual-menu', 'name_ar' => 'مطعم الريف', 'name_en' => 'Al Reef',
            'description_ar' => 'وصف عربي', 'description_en' => 'English description', 'default_language' => 'en',
        ]);
        $category = Category::factory()->create(['restaurant_id' => $restaurant->id, 'name_ar' => 'مشروبات', 'name_en' => 'Drinks']);
        MenuItem::factory()->create(['restaurant_id' => $restaurant->id, 'category_id' => $category->id, 'name_ar' => 'قهوة', 'name_en' => 'Coffee', 'description_ar' => 'ساخنة', 'description_en' => 'Hot']);

        $this->getJson('/api/v1/public/menu/bilingual-menu')->assertOk()
            ->assertJsonPath('data.menu.language', 'en')->assertJsonPath('data.menu.name', 'Al Reef')
            ->assertJsonPath('data.menu.categories.0.name', 'Drinks')->assertJsonPath('data.menu.categories.0.menu_items.0.name', 'Coffee');
        $this->getJson('/api/v1/public/menu/bilingual-menu?lang=ar')->assertOk()
            ->assertJsonPath('data.menu.language', 'ar')->assertJsonPath('data.menu.name', 'مطعم الريف')
            ->assertJsonPath('data.menu.categories.0.menu_items.0.description', 'ساخنة');
    }

    public function test_public_menu_falls_back_and_rejects_invalid_language_without_leaking_hidden_content(): void
    {
        $restaurant = Restaurant::factory()->create(['slug' => 'fallback-menu', 'name_ar' => 'عربي', 'name_en' => null]);
        $visible = Category::factory()->create(['restaurant_id' => $restaurant->id, 'name_ar' => 'مرئي', 'name_en' => null]);
        $hidden = Category::factory()->create(['restaurant_id' => $restaurant->id, 'name' => 'Hidden', 'is_active' => false]);
        MenuItem::factory()->create(['restaurant_id' => $restaurant->id, 'category_id' => $visible->id, 'name_ar' => 'متاح', 'name_en' => null]);
        MenuItem::factory()->create(['restaurant_id' => $restaurant->id, 'category_id' => $hidden->id, 'name' => 'Secret']);

        $this->getJson('/api/v1/public/menu/fallback-menu?lang=en')->assertOk()
            ->assertJsonPath('data.menu.name', 'عربي')->assertJsonFragment(['name' => 'متاح'])->assertJsonMissing(['name' => 'Secret']);
        $this->getJson('/api/v1/public/menu/fallback-menu?lang=fr')->assertUnprocessable();
    }

    private function actingOwner(): Restaurant
    {
        $user = User::factory()->create();
        $restaurant = Restaurant::factory()->for($user, 'owner')->create();
        Sanctum::actingAs($user);

        return $restaurant;
    }
}
