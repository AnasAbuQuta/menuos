<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminRestaurantResource;
use App\Http\Resources\Admin\AdminUserResource;
use App\Services\AdminPlatformService;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function __invoke(AdminPlatformService $service): JsonResponse
    {
        $dashboard = $service->dashboard();

        return response()->json(['message' => 'Platform dashboard retrieved.', 'data' => [
            'metrics' => $dashboard['metrics'],
            'latest_users' => AdminUserResource::collection($dashboard['latest_users']),
            'latest_restaurants' => AdminRestaurantResource::collection($dashboard['latest_restaurants']),
        ]]);
    }
}
