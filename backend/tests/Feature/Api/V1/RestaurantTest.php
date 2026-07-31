<?php

namespace Tests\Feature\Api\V1;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $user = User::factory()->create();
        Restaurant::factory()->for($user, 'owner')->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/restaurant', ['name' => 'Another Restaurant'])
            ->assertForbidden();
        $this->assertDatabaseCount('restaurants', 1);
    }

    public function test_restaurant_ownership_is_protected(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $restaurant = Restaurant::factory()->for($owner, 'owner')->create();

        $this->assertFalse($otherUser->can('view', $restaurant));
        $this->assertFalse($otherUser->can('update', $restaurant));

        Sanctum::actingAs($owner);
        $this->putJson('/api/v1/restaurant', [
            'name' => 'Changed', 'owner_id' => $otherUser->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('owner_id');
        $this->assertSame($owner->id, $restaurant->fresh()->owner_id);
    }

    public function test_owner_can_update_restaurant(): void
    {
        $user = User::factory()->create();
        $restaurant = Restaurant::factory()->for($user, 'owner')->create([
            'name' => 'Old Name', 'slug' => 'old-name',
        ]);
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/restaurant', [
            'name' => 'New Name', 'description' => 'Updated description',
            'primary_color' => '#112233',
        ])->assertOk()
            ->assertJsonPath('data.restaurant.name', 'New Name')
            ->assertJsonPath('data.restaurant.slug', 'new-name');

        $this->assertDatabaseHas('restaurants', [
            'id' => $restaurant->id, 'owner_id' => $user->id,
            'description' => 'Updated description',
        ]);
    }
}
