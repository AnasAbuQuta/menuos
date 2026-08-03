<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('public-menu', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));
        RateLimiter::for('public-analytics', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));
    }
}
