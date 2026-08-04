<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use Database\Seeders\DemoRestaurantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoRestaurantSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_bella_pasta_demo_is_complete_and_idempotent(): void
    {
        Storage::fake('public');
        config(['demo.owner_email' => 'bella@example.com', 'demo.owner_password' => 'A-secure-demo-password-123']);

        $this->seed(DemoRestaurantSeeder::class);
        $this->seed(DemoRestaurantSeeder::class);

        $this->assertSame(1, User::query()->where('email', 'bella@example.com')->count());
        $restaurant = Restaurant::query()->where('slug', 'bella-pasta')->firstOrFail();
        $this->assertSame('بيلا باستا', $restaurant->name_ar);
        $this->assertSame('cafe', $restaurant->theme_key);
        $this->assertSame(3, Category::query()->where('restaurant_id', $restaurant->id)->count());
        $this->assertSame(5, MenuItem::query()->where('restaurant_id', $restaurant->id)->count());
        Storage::disk('public')->assertExists('demo/bella-pasta-logo.webp');
        Storage::disk('public')->assertExists('demo/bella-pasta-cover.webp');
    }
}
