<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_contains_landing_and_active_restaurants_only(): void
    {
        config(['app.public_frontend_url' => 'https://menus.example']);
        Restaurant::factory()->create(['slug' => 'bella-pasta', 'is_active' => true]);
        Restaurant::factory()->create(['slug' => 'private-menu', 'is_active' => false]);

        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->assertSee('https://menus.example/')->assertSee('https://menus.example/menu/bella-pasta')->assertDontSee('private-menu');
    }
}
