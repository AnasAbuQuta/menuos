<?php

namespace Tests\Feature\Api\V1;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RestaurantQrCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_owner_can_retrieve_qr_for_the_correct_public_menu_url(): void
    {
        config(['app.public_frontend_url' => 'https://menu.example.com/']);
        $user = User::factory()->create();
        Restaurant::factory()->for($user, 'owner')->create(['slug' => 'owners-cafe']);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/restaurant/qr-code')
            ->assertOk()
            ->assertJsonPath('data.public_menu_url', 'https://menu.example.com/menu/owners-cafe')
            ->assertJson(fn ($json) => $json
                ->whereType('data.qr_code', 'string')
                ->where('data.qr_code', fn ($value) => str_starts_with($value, 'data:image/png;base64,'))
                ->missing('data.restaurant')
                ->etc());
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/restaurant/qr-code')->assertUnauthorized();
    }

    public function test_user_without_restaurant_receives_clear_error(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/restaurant/qr-code')
            ->assertNotFound()
            ->assertJsonPath('message', 'Restaurant not found.');
    }

    public function test_qr_endpoint_never_exposes_another_users_restaurant(): void
    {
        config(['app.public_frontend_url' => 'https://menus.example']);
        $owner = User::factory()->create();
        Restaurant::factory()->for($owner, 'owner')->create(['slug' => 'private-owner']);
        $otherUser = User::factory()->create();
        Restaurant::factory()->for($otherUser, 'owner')->create(['slug' => 'my-menu']);
        Sanctum::actingAs($otherUser);

        $this->getJson('/api/v1/restaurant/qr-code')
            ->assertOk()
            ->assertJsonPath('data.public_menu_url', 'https://menus.example/menu/my-menu')
            ->assertJsonMissing(['private-owner']);
    }
}
