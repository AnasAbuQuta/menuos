<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Http\Requests\Admin\UserIndexRequest;
use App\Http\Resources\Admin\AdminUserResource;
use App\Models\User;
use App\Services\AdminPlatformService;
use Illuminate\Http\JsonResponse;

class AdminUserController extends Controller
{
    public function __construct(private readonly AdminPlatformService $service) {}

    public function index(UserIndexRequest $request): JsonResponse
    {
        $users = $this->service->users($request->validated());

        return response()->json(['message' => 'Platform users retrieved.', 'data' => [
            'users' => AdminUserResource::collection($users->items()),
            'meta' => $this->meta($users),
        ]]);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json(['message' => 'Platform user retrieved.', 'data' => ['user' => new AdminUserResource($this->service->user($user))]]);
    }

    public function status(UpdateUserStatusRequest $request, User $user): JsonResponse
    {
        $user = $this->service->updateUserStatus($request->user(), $user, $request->validated('status'));

        return response()->json(['message' => 'User account status updated.', 'data' => ['user' => new AdminUserResource($user)]]);
    }

    private function meta($paginator): array
    {
        return ['current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total()];
    }
}
