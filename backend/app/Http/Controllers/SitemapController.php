<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $base = rtrim((string) config('app.public_frontend_url'), '/');
        $urls = collect([['loc' => $base.'/', 'lastmod' => now()->toDateString()]])
            ->concat(Restaurant::query()->where('is_active', true)->orderBy('id')->get(['slug', 'updated_at'])->map(fn (Restaurant $restaurant) => ['loc' => $base.'/menu/'.$restaurant->slug, 'lastmod' => $restaurant->updated_at?->toDateString()]));
        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8', 'Cache-Control' => 'public, max-age=3600']);
    }
}
