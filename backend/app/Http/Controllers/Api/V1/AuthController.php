<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        [$user, $token] = $this->authService->register($request->validated());

        return response()->json([
            'message' => 'Registration successful.',
            'data' => ['user' => new UserResource($user->load('restaurant')), 'token' => $token],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        [$user, $token] = $this->authService->login($request->validated());

        return response()->json([
            'message' => 'Login successful.',
            'data' => ['user' => new UserResource($user->load('restaurant')), 'token' => $token],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout successful.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Authenticated user retrieved.',
            'data' => ['user' => new UserResource($request->user()->load('restaurant'))],
        ]);
    }
}
