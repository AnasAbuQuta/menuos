<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RestaurantIndexRequest;
use App\Http\Requests\Admin\UpdateRestaurantStatusRequest;
use App\Http\Resources\Admin\AdminRestaurantResource;
use App\Models\Restaurant;
use App\Services\AdminPlatformService;
use Illuminate\Http\JsonResponse;

class AdminRestaurantController extends Controller
{
    public function __construct(private readonly AdminPlatformService $service) {}

    public function index(RestaurantIndexRequest $request): JsonResponse
    {
        $restaurants = $this->service->restaurants($request->validated());

        return response()->json(['message' => 'Platform restaurants retrieved.', 'data' => [
            'restaurants' => AdminRestaurantResource::collection($restaurants->items()),
            'meta' => ['current_page' => $restaurants->currentPage(), 'last_page' => $restaurants->lastPage(), 'per_page' => $restaurants->perPage(), 'total' => $restaurants->total()],
        ]]);
    }

    public function show(Restaurant $restaurant): JsonResponse
    {
        return response()->json(['message' => 'Platform restaurant retrieved.', 'data' => ['restaurant' => new AdminRestaurantResource($this->service->restaurant($restaurant))]]);
    }

    public function status(UpdateRestaurantStatusRequest $request, Restaurant $restaurant): JsonResponse
    {
        $restaurant = $this->service->updateRestaurantStatus($restaurant, $request->validated('status'));

        return response()->json(['message' => 'Restaurant platform status updated.', 'data' => ['restaurant' => new AdminRestaurantResource($restaurant)]]);
    }
}
