<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\ReorderCategoriesRequest;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Models\Restaurant;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categoryService) {}

    public function index(Request $request): JsonResponse
    {
        $restaurant = $this->restaurant($request);
        $this->authorize('viewAny', Category::class);

        return response()->json([
            'message' => 'Categories retrieved.',
            'data' => ['categories' => CategoryResource::collection($restaurant->categories()->ordered()->get())],
        ]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $restaurant = $this->restaurant($request);
        $this->authorize('create', Category::class);
        $category = $this->categoryService->create($restaurant, $request->validated());

        return $this->response($category, 'Category created.', 201);
    }

    public function show(Request $request, int $category): JsonResponse
    {
        $model = $this->category($request, $category);
        $this->authorize('view', $model);

        return $this->response($model, 'Category retrieved.');
    }

    public function update(UpdateCategoryRequest $request, int $category): JsonResponse
    {
        $model = $this->category($request, $category);
        $this->authorize('update', $model);
        $model = $this->categoryService->update($model, $request->validated());

        return $this->response($model, 'Category updated.');
    }

    public function destroy(Request $request, int $category): JsonResponse
    {
        $model = $this->category($request, $category);
        $this->authorize('delete', $model);
        $this->categoryService->delete($model);

        return response()->json(['message' => 'Category deleted.']);
    }

    public function reorder(ReorderCategoriesRequest $request): JsonResponse
    {
        $restaurant = $this->restaurant($request);
        $this->authorize('viewAny', Category::class);
        $categories = $this->categoryService->reorder($restaurant, $request->validated('category_ids'));

        return response()->json([
            'message' => 'Categories reordered.',
            'data' => ['categories' => CategoryResource::collection($categories)],
        ]);
    }

    private function restaurant(Request $request): Restaurant
    {
        $restaurant = $request->user()->restaurant;
        abort_if(! $restaurant, 409, 'Create a restaurant before managing categories.');

        return $restaurant;
    }

    private function category(Request $request, int $category): Category
    {
        return $this->restaurant($request)->categories()->findOrFail($category);
    }

    private function response(Category $category, string $message, int $status = 200): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => ['category' => new CategoryResource($category)],
        ], $status);
    }
}
