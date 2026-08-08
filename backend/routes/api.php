<?php

use App\Http\Controllers\Api\V1\Admin\AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\AdminRestaurantController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\MenuItemController;
use App\Http\Controllers\Api\V1\PublicMenuController;
use App\Http\Controllers\Api\V1\RestaurantController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('public/menu/{slug}', PublicMenuController::class)
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
        ->middleware('throttle:public-menu');
    Route::post('public/menu/{slug}/analytics', [AnalyticsController::class, 'store'])
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
        ->middleware('throttle:public-analytics');

    Route::prefix('auth')->group(function (): void {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);

        Route::middleware(['auth:sanctum', 'account.active'])->group(function (): void {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    Route::middleware(['auth:sanctum', 'account.active', 'super.admin'])->prefix('admin')->group(function (): void {
        Route::get('dashboard', AdminDashboardController::class);
        Route::get('users', [AdminUserController::class, 'index']);
        Route::get('users/{user}', [AdminUserController::class, 'show']);
        Route::patch('users/{user}/status', [AdminUserController::class, 'status']);
        Route::get('restaurants', [AdminRestaurantController::class, 'index']);
        Route::get('restaurants/{restaurant}', [AdminRestaurantController::class, 'show']);
        Route::patch('restaurants/{restaurant}/status', [AdminRestaurantController::class, 'status']);
    });

    Route::middleware(['auth:sanctum', 'account.active'])->prefix('restaurant')->group(function (): void {
        Route::get('/', [RestaurantController::class, 'show']);
        Route::post('/', [RestaurantController::class, 'store']);
        Route::put('/', [RestaurantController::class, 'update']);
        Route::post('logo', [RestaurantController::class, 'uploadLogo']);
        Route::post('cover', [RestaurantController::class, 'uploadCover']);
        Route::delete('logo', [RestaurantController::class, 'deleteLogo']);
        Route::delete('cover', [RestaurantController::class, 'deleteCover']);
        Route::get('qr-code', [RestaurantController::class, 'qrCode']);
        Route::get('analytics', [AnalyticsController::class, 'dashboard']);
    });

    Route::middleware(['auth:sanctum', 'account.active'])->prefix('categories')->group(function (): void {
        Route::get('/', [CategoryController::class, 'index']);
        Route::post('/', [CategoryController::class, 'store']);
        Route::post('reorder', [CategoryController::class, 'reorder']);
        Route::get('{category}', [CategoryController::class, 'show']);
        Route::put('{category}', [CategoryController::class, 'update']);
        Route::delete('{category}', [CategoryController::class, 'destroy']);
    });

    Route::middleware(['auth:sanctum', 'account.active'])->prefix('menu-items')->group(function (): void {
        Route::get('/', [MenuItemController::class, 'index']);
        Route::post('/', [MenuItemController::class, 'store']);
        Route::post('reorder', [MenuItemController::class, 'reorder']);
        Route::get('{menuItem}', [MenuItemController::class, 'show']);
        Route::put('{menuItem}', [MenuItemController::class, 'update']);
        Route::post('{menuItem}', [MenuItemController::class, 'update']);
        Route::delete('{menuItem}', [MenuItemController::class, 'destroy']);
    });
});
