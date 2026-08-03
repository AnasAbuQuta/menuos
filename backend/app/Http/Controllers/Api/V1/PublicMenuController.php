<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Public\PublicRestaurantMenuResource;
use App\Services\PublicMenuService;
use Illuminate\Http\JsonResponse;

class PublicMenuController extends Controller
{
    public function __construct(private readonly PublicMenuService $publicMenuService) {}

    public function __invoke(string $slug): JsonResponse
    {
        return response()->json([
            'message' => 'Public menu retrieved.',
            'data' => ['menu' => new PublicRestaurantMenuResource($this->publicMenuService->findBySlug($slug))],
        ]);
    }
}
