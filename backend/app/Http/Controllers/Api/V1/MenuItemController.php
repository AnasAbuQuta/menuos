<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenuItem\ListMenuItemsRequest;
use App\Http\Requests\MenuItem\ReorderMenuItemsRequest;
use App\Http\Requests\MenuItem\StoreMenuItemRequest;
use App\Http\Requests\MenuItem\UpdateMenuItemRequest;
use App\Http\Resources\MenuItemResource;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Services\MenuItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuItemController extends Controller
{
    public function __construct(private readonly MenuItemService $menuItemService) {}

    public function index(ListMenuItemsRequest $request): JsonResponse
    {
        $restaurant = $this->restaurant($request);
        $this->authorize('viewAny', MenuItem::class);
        $items = $this->menuItemService->list($restaurant, $request->validated());

        return response()->json([
            'message' => 'Menu items retrieved.',
            'data' => ['menu_items' => MenuItemResource::collection($items)],
        ]);
    }

    public function store(StoreMenuItemRequest $request): JsonResponse
    {
        $restaurant = $this->restaurant($request);
        $this->authorize('create', MenuItem::class);
        $item = $this->menuItemService->create($restaurant, $request->validated(), $request->file('image'));

        return $this->response($item, 'Menu item created.', 201);
    }

    public function show(Request $request, int $menuItem): JsonResponse
    {
        $item = $this->menuItem($request, $menuItem);
        $this->authorize('view', $item);

        return $this->response($item, 'Menu item retrieved.');
    }

    public function update(UpdateMenuItemRequest $request, int $menuItem): JsonResponse
    {
        $item = $this->menuItem($request, $menuItem);
        $this->authorize('update', $item);
        $item = $this->menuItemService->update($item, $request->validated(), $request->file('image'));

        return $this->response($item, 'Menu item updated.');
    }

    public function destroy(Request $request, int $menuItem): JsonResponse
    {
        $item = $this->menuItem($request, $menuItem);
        $this->authorize('delete', $item);
        $this->menuItemService->delete($item);

        return response()->json(['message' => 'Menu item deleted.']);
    }

    public function reorder(ReorderMenuItemsRequest $request): JsonResponse
    {
        $restaurant = $this->restaurant($request);
        $this->authorize('viewAny', MenuItem::class);
        $items = $this->menuItemService->reorder(
            $restaurant,
            (int) $request->validated('category_id'),
            $request->validated('menu_item_ids'),
        );

        return response()->json([
            'message' => 'Menu items reordered.',
            'data' => ['menu_items' => MenuItemResource::collection($items)],
        ]);
    }

    private function restaurant(Request $request): Restaurant
    {
        $restaurant = $request->user()->restaurant;
        abort_if(! $restaurant, 409, 'Create a restaurant before managing menu items.');

        return $restaurant;
    }

    private function menuItem(Request $request, int $menuItem): MenuItem
    {
        return $this->restaurant($request)->menuItems()->with('category:id,name')->findOrFail($menuItem);
    }

    private function response(MenuItem $item, string $message, int $status = 200): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => ['menu_item' => new MenuItemResource($item)],
        ], $status);
    }
}
