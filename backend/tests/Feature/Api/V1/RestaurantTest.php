<?php

namespace Tests\Feature\Api\V1;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RestaurantTest extends TestCase
{
    use RefreshDatabase;

    public function test_restaurant_can_be_created_with_a_safe_slug(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->postJson('/api/v1/restaurant', ['name' => 'مطعم القدس'])
            ->assertCreated()->assertJsonPath('data.restaurant.currency', 'ILS');
        $restaurant = Restaurant::firstOrFail();
        $this->assertSame($user->id, $restaurant->owner_id);
        $this->assertMatchesRegularExpression('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $restaurant->slug);
    }

    public function test_user_cannot_create_a_second_restaurant(): void
    {
        $this->actingOwner();
        $this->postJson('/api/v1/restaurant', ['name' => 'Another Restaurant'])->assertForbidden();
        $this->assertDatabaseCount('restaurants', 1);
    }

    public function test_owner_can_retrieve_own_restaurant_settings(): void
    {
        $restaurant = $this->actingOwner(['name' => 'My Restaurant']);
        $this->getJson('/api/v1/restaurant')->assertOk()
            ->assertJsonPath('data.restaurant.id', $restaurant->id)
            ->assertJsonPath('data.restaurant.name', 'My Restaurant')
            ->assertJsonMissingPath('data.restaurant.owner_id')
            ->assertJsonMissingPath('data.restaurant.logo');
    }

    public function test_owner_can_update_general_settings_and_slug_remains_stable(): void
    {
        $restaurant = $this->actingOwner(['name' => 'Old', 'slug' => 'stable-slug']);
        $this->putJson('/api/v1/restaurant', [
            'name' => '  New Name  ', 'description' => 'Updated description',
            'address' => 'Main Street', 'currency' => 'usd',
            'primary_color' => '#e63946', 'is_active' => false,
        ])->assertOk()
            ->assertJsonPath('data.restaurant.name', 'New Name')
            ->assertJsonPath('data.restaurant.slug', 'stable-slug')
            ->assertJsonPath('data.restaurant.description', 'Updated description')
            ->assertJsonPath('data.restaurant.address', 'Main Street')
            ->assertJsonPath('data.restaurant.currency', 'USD')
            ->assertJsonPath('data.restaurant.primary_color', '#E63946')
            ->assertJsonPath('data.restaurant.is_active', false);
        $this->assertSame('stable-slug', $restaurant->fresh()->slug);
    }

    public function test_unsupported_currency_and_invalid_color_are_rejected(): void
    {
        $this->actingOwner();
        $this->putJson('/api/v1/restaurant', ['currency' => 'EUR'])
            ->assertUnprocessable()->assertJsonValidationErrors('currency');
        $this->putJson('/api/v1/restaurant', ['primary_color' => 'red'])
            ->assertUnprocessable()->assertJsonValidationErrors('primary_color');
    }

    public function test_owner_id_slug_and_internal_image_paths_are_prohibited(): void
    {
        $restaurant = $this->actingOwner();
        $this->putJson('/api/v1/restaurant', [
            'owner_id' => User::factory()->create()->id,
            'slug' => 'changed', 'logo' => 'fake.jpg', 'cover_image' => 'fake.jpg',
        ])->assertUnprocessable()->assertJsonValidationErrors(['owner_id', 'slug', 'logo', 'cover_image']);
        $this->assertSame($restaurant->owner_id, $restaurant->fresh()->owner_id);
    }

    public function test_foreign_user_is_not_authorized_for_another_restaurant(): void
    {
        $restaurant = $this->actingOwner();
        $other = User::factory()->create();
        $this->assertFalse($other->can('view', $restaurant));
        $this->assertFalse($other->can('update', $restaurant));
        $this->assertFalse($other->can('manageImages', $restaurant));
    }

    public function test_valid_complete_opening_hours_are_accepted_and_returned_as_json(): void
    {
        $this->actingOwner();
        $hours = $this->openingHours();
        $this->putJson('/api/v1/restaurant', ['opening_hours' => $hours])->assertOk()
            ->assertJsonPath('data.restaurant.opening_hours.saturday.open', '09:00')
            ->assertJsonPath('data.restaurant.opening_hours.friday.is_open', false)
            ->assertJsonPath('data.restaurant.opening_hours.friday.open', null);
    }

    public function test_open_day_requires_both_valid_times(): void
    {
        $this->actingOwner();
        $hours = $this->openingHours();
        $hours['monday']['close'] = null;
        $this->putJson('/api/v1/restaurant', ['opening_hours' => $hours])
            ->assertUnprocessable()->assertJsonValidationErrors('opening_hours.monday.close');
        $hours = $this->openingHours();
        $hours['monday']['open'] = '9 AM';
        $this->putJson('/api/v1/restaurant', ['opening_hours' => $hours])
            ->assertUnprocessable()->assertJsonValidationErrors('opening_hours.monday.open');
    }

    public function test_missing_day_structure_is_rejected(): void
    {
        $this->actingOwner();
        $hours = $this->openingHours();
        unset($hours['friday']);
        $this->putJson('/api/v1/restaurant', ['opening_hours' => $hours])
            ->assertUnprocessable()->assertJsonValidationErrors('opening_hours.friday');
    }

    public function test_phone_and_whatsapp_are_normalized_without_guessing_country_code(): void
    {
        $this->actingOwner();
        $this->putJson('/api/v1/restaurant', [
            'phone' => '+970 (59) 123-4567', 'whatsapp' => '059-765 4321',
        ])->assertOk()
            ->assertJsonPath('data.restaurant.phone', '+970591234567')
            ->assertJsonPath('data.restaurant.whatsapp', '0597654321');
    }

    public function test_phone_fields_reject_clearly_invalid_characters(): void
    {
        $this->actingOwner();
        $this->putJson('/api/v1/restaurant', ['phone' => '+970-CALL-NOW', 'whatsapp' => '123@456'])
            ->assertUnprocessable()->assertJsonValidationErrors(['phone', 'whatsapp']);
    }

    public function test_valid_logo_upload_succeeds_and_resource_returns_full_url(): void
    {
        Storage::fake('public');
        $restaurant = $this->actingOwner();
        $response = $this->post('/api/v1/restaurant/logo', [
            'image' => UploadedFile::fake()->image('logo.webp'),
        ], ['Accept' => 'application/json'])->assertOk();
        $path = $restaurant->fresh()->logo;
        Storage::disk('public')->assertExists($path);
        $this->assertStringStartsWith("restaurants/{$restaurant->id}/logo/", $path);
        $this->assertStringContainsString('/storage/restaurants/', $response->json('data.restaurant.logo_url'));
        $response->assertJsonMissingPath('data.restaurant.logo');
    }

    public function test_valid_cover_upload_succeeds(): void
    {
        Storage::fake('public');
        $restaurant = $this->actingOwner();
        $this->post('/api/v1/restaurant/cover', [
            'image' => UploadedFile::fake()->image('cover.jpg'),
        ], ['Accept' => 'application/json'])->assertOk();
        $path = $restaurant->fresh()->cover_image;
        Storage::disk('public')->assertExists($path);
        $this->assertStringStartsWith("restaurants/{$restaurant->id}/cover/", $path);
    }

    public function test_invalid_and_oversized_restaurant_images_are_rejected(): void
    {
        Storage::fake('public');
        $this->actingOwner();
        $this->post('/api/v1/restaurant/logo', [
            'image' => UploadedFile::fake()->createWithContent('logo.php', '<?php'),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('image');
        $this->post('/api/v1/restaurant/logo', [
            'image' => UploadedFile::fake()->image('large.jpg')->size(2049),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('image');
        $this->post('/api/v1/restaurant/cover', [
            'image' => UploadedFile::fake()->image('large.webp')->size(2049),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('image');
    }

    public function test_replacing_logo_and_cover_deletes_previous_files(): void
    {
        Storage::fake('public');
        $restaurant = $this->actingOwner();
        $oldLogo = "restaurants/{$restaurant->id}/logo/old.jpg";
        $oldCover = "restaurants/{$restaurant->id}/cover/old.jpg";
        Storage::disk('public')->put($oldLogo, 'old');
        Storage::disk('public')->put($oldCover, 'old');
        $restaurant->update(['logo' => $oldLogo, 'cover_image' => $oldCover]);

        $this->post('/api/v1/restaurant/logo', ['image' => UploadedFile::fake()->image('new.png')], ['Accept' => 'application/json'])->assertOk();
        $this->post('/api/v1/restaurant/cover', ['image' => UploadedFile::fake()->image('new.webp')], ['Accept' => 'application/json'])->assertOk();
        Storage::disk('public')->assertMissing($oldLogo);
        Storage::disk('public')->assertMissing($oldCover);
        Storage::disk('public')->assertExists($restaurant->fresh()->logo);
        Storage::disk('public')->assertExists($restaurant->fresh()->cover_image);
    }

    public function test_logo_and_cover_deletion_clear_fields_and_files(): void
    {
        Storage::fake('public');
        $restaurant = $this->actingOwner();
        $logo = "restaurants/{$restaurant->id}/logo/current.jpg";
        $cover = "restaurants/{$restaurant->id}/cover/current.jpg";
        Storage::disk('public')->put($logo, 'logo');
        Storage::disk('public')->put($cover, 'cover');
        $restaurant->update(['logo' => $logo, 'cover_image' => $cover]);
        $this->deleteJson('/api/v1/restaurant/logo')->assertOk()->assertJsonPath('data.restaurant.logo_url', null);
        $this->deleteJson('/api/v1/restaurant/cover')->assertOk()->assertJsonPath('data.restaurant.cover_image_url', null);
        Storage::disk('public')->assertMissing($logo);
        Storage::disk('public')->assertMissing($cover);
        $this->assertNull($restaurant->fresh()->logo);
        $this->assertNull($restaurant->fresh()->cover_image);
    }

    public function test_failed_replacement_validation_preserves_existing_image(): void
    {
        Storage::fake('public');
        $restaurant = $this->actingOwner();
        $path = "restaurants/{$restaurant->id}/logo/current.jpg";
        Storage::disk('public')->put($path, 'current');
        $restaurant->update(['logo' => $path]);
        $this->post('/api/v1/restaurant/logo', [
            'image' => UploadedFile::fake()->create('bad.txt', 1, 'text/plain'),
        ], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->assertSame($path, $restaurant->fresh()->logo);
        Storage::disk('public')->assertExists($path);
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_supported_currency_and_theme_are_accepted(): void
    {
        $restaurant = $this->actingOwner();

        $this->putJson('/api/v1/restaurant', ['currency' => 'USD', 'theme_key' => 'warm'])
            ->assertOk()->assertJsonPath('data.restaurant.currency', 'USD')
            ->assertJsonPath('data.restaurant.theme_key', 'warm');

        $this->assertDatabaseHas('restaurants', ['id' => $restaurant->id, 'currency' => 'USD', 'theme_key' => 'warm']);
    }

    public function test_unsupported_currency_and_theme_are_rejected(): void
    {
        $restaurant = $this->actingOwner(['currency' => 'ILS', 'theme_key' => 'modern']);

        $this->putJson('/api/v1/restaurant', ['currency' => 'EUR', 'theme_key' => 'custom<script>'])
            ->assertUnprocessable()->assertJsonValidationErrors(['currency', 'theme_key']);

        $this->assertSame('ILS', $restaurant->fresh()->currency);
        $this->assertSame('modern', $restaurant->fresh()->theme_key);
    }

    public function test_new_restaurants_receive_the_default_theme(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/restaurant', ['name' => 'Default Theme Restaurant', 'currency' => 'JOD'])
            ->assertCreated()->assertJsonPath('data.restaurant.theme_key', 'modern')
            ->assertJsonPath('data.restaurant.currency', 'JOD');
    }

    private function actingOwner(array $attributes = []): Restaurant
    {
        $user = User::factory()->create();
        $restaurant = Restaurant::factory()->for($user, 'owner')->create($attributes);
        Sanctum::actingAs($user);

        return $restaurant;
    }

    private function openingHours(): array
    {
        $open = ['is_open' => true, 'open' => '09:00', 'close' => '23:00'];

        return [
            'saturday' => $open, 'sunday' => $open, 'monday' => $open,
            'tuesday' => $open, 'wednesday' => $open, 'thursday' => $open,
            'friday' => ['is_open' => false, 'open' => null, 'close' => null],
        ];
    }
}
