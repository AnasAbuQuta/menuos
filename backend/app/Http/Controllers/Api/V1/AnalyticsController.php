<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Analytics\StoreAnalyticsEventsRequest;
use App\Models\Restaurant;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analyticsService) {}

    public function store(StoreAnalyticsEventsRequest $request, string $slug): JsonResponse
    {
        $restaurant = Restaurant::query()->where('slug', $slug)->where('is_active', true)->where('platform_status', 'active')->firstOrFail();
        $this->analyticsService->record($restaurant, $request->validated('visitor_id'), $request->validated('events'));

        return response()->json(['message' => 'Analytics events accepted.'], 202)->header('Cache-Control', 'no-store');
    }

    public function dashboard(Request $request): JsonResponse
    {
        $restaurant = $request->user()->restaurant;
        abort_if(! $restaurant, 404, 'Restaurant not found.');
        $this->authorize('view', $restaurant);

        return response()->json(['message' => 'Analytics retrieved.', 'data' => ['analytics' => $this->analyticsService->dashboard($restaurant)]])->header('Cache-Control', 'no-store');
    }
}
