<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Restaurant\StoreRestaurantRequest;
use App\Http\Requests\Restaurant\UpdateRestaurantRequest;
use App\Http\Resources\RestaurantResource;
use App\Models\Restaurant;
use App\Services\RestaurantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    public function __construct(private readonly RestaurantService $restaurantService) {}

    public function show(Request $request): JsonResponse
    {
        $restaurant = $request->user()->restaurant;
        abort_if(! $restaurant, 404, 'Restaurant not found.');
        $this->authorize('view', $restaurant);

        return $this->response($restaurant, 'Restaurant retrieved.');
    }

    public function store(StoreRestaurantRequest $request): JsonResponse
    {
        $this->authorize('create', Restaurant::class);
        $restaurant = $this->restaurantService->create($request->user(), $request->validated());

        return $this->response($restaurant, 'Restaurant created.', 201);
    }

    public function update(UpdateRestaurantRequest $request): JsonResponse
    {
        $restaurant = $request->user()->restaurant;
        abort_if(! $restaurant, 404, 'Restaurant not found.');
        $this->authorize('update', $restaurant);
        $restaurant = $this->restaurantService->update($restaurant, $request->validated());

        return $this->response($restaurant, 'Restaurant updated.');
    }

    private function response(Restaurant $restaurant, string $message, int $status = 200): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => ['restaurant' => new RestaurantResource($restaurant)],
        ], $status);
    }
}
