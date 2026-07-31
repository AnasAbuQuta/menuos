<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\RestaurantController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    Route::middleware('auth:sanctum')->prefix('restaurant')->group(function (): void {
        Route::get('/', [RestaurantController::class, 'show']);
        Route::post('/', [RestaurantController::class, 'store']);
        Route::put('/', [RestaurantController::class, 'update']);
    });

    Route::middleware('auth:sanctum')->prefix('categories')->group(function (): void {
        Route::get('/', [CategoryController::class, 'index']);
        Route::post('/', [CategoryController::class, 'store']);
        Route::post('reorder', [CategoryController::class, 'reorder']);
        Route::get('{category}', [CategoryController::class, 'show']);
        Route::put('{category}', [CategoryController::class, 'update']);
        Route::delete('{category}', [CategoryController::class, 'destroy']);
    });
});
