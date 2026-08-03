<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Restaurant\StoreRestaurantRequest;
use App\Http\Requests\Restaurant\UpdateRestaurantRequest;
use App\Http\Requests\Restaurant\UploadRestaurantImageRequest;
use App\Http\Resources\RestaurantResource;
use App\Models\Restaurant;
use App\Services\RestaurantQrCodeService;
use App\Services\RestaurantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    public function __construct(
        private readonly RestaurantService $restaurantService,
        private readonly RestaurantQrCodeService $restaurantQrCodeService,
    ) {}

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

    public function uploadLogo(UploadRestaurantImageRequest $request): JsonResponse
    {
        return $this->uploadImage($request, 'logo', 'logo', 'Restaurant logo updated.');
    }

    public function uploadCover(UploadRestaurantImageRequest $request): JsonResponse
    {
        return $this->uploadImage($request, 'cover_image', 'cover', 'Restaurant cover updated.');
    }

    public function deleteLogo(Request $request): JsonResponse
    {
        return $this->deleteImage($request, 'logo', 'Restaurant logo removed.');
    }

    public function deleteCover(Request $request): JsonResponse
    {
        return $this->deleteImage($request, 'cover_image', 'Restaurant cover removed.');
    }

    public function qrCode(Request $request): JsonResponse
    {
        $restaurant = $this->ownedRestaurant($request);
        $this->authorize('view', $restaurant);

        return response()->json([
            'message' => 'Restaurant QR code generated.',
            'data' => $this->restaurantQrCodeService->generate($restaurant),
        ]);
    }

    private function uploadImage(UploadRestaurantImageRequest $request, string $field, string $directory, string $message): JsonResponse
    {
        $restaurant = $this->ownedRestaurant($request);
        $this->authorize('manageImages', $restaurant);
        $restaurant = $this->restaurantService->uploadImage($restaurant, $request->file('image'), $field, $directory);

        return $this->response($restaurant, $message);
    }

    private function deleteImage(Request $request, string $field, string $message): JsonResponse
    {
        $restaurant = $this->ownedRestaurant($request);
        $this->authorize('manageImages', $restaurant);
        $restaurant = $this->restaurantService->deleteImage($restaurant, $field);

        return $this->response($restaurant, $message);
    }

    private function ownedRestaurant(Request $request): Restaurant
    {
        $restaurant = $request->user()->restaurant;
        abort_if(! $restaurant, 404, 'Restaurant not found.');

        return $restaurant;
    }

    private function response(Restaurant $restaurant, string $message, int $status = 200): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => ['restaurant' => new RestaurantResource($restaurant)],
        ], $status);
    }
}
