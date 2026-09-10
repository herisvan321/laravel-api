<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Get the JWT guard instance.
     *
     * @return \PHPOpenSourceSaver\JWTAuth\JWTGuard
     */
    protected function guard(): \PHPOpenSourceSaver\JWTAuth\JWTGuard
    {
        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = auth('api');

        return $guard;
    }

    /**
     * Register a new user and generate a JWT token.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $token = $this->guard()->login($user);

        return ApiResponse::success([
            'user' => $user,
            'authorization' => [
                'token' => $token,
                'type' => 'bearer',
                'expires_in' => $this->guard()->factory()->getTTL() * 60,
            ],
        ], 'User registered successfully', 201);
    }

    /**
     * Authenticate user and return a JWT token.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $token = $this->guard()->attempt($credentials);

        if (! $token) {
            return ApiResponse::error('Invalid email or password.', 401);
        }

        return ApiResponse::success([
            'user' => $this->guard()->user(),
            'authorization' => [
                'token' => $token,
                'type' => 'bearer',
                'expires_in' => $this->guard()->factory()->getTTL() * 60,
            ],
        ], 'Login successful');
    }

    /**
     * Get the authenticated User profile.
     */
    public function me(): JsonResponse
    {
        return ApiResponse::success($this->guard()->user(), 'User profile retrieved successfully');
    }

    /**
     * Refresh an existing JWT token.
     */
    public function refresh(): JsonResponse
    {
        $token = $this->guard()->refresh();

        return ApiResponse::success([
            'authorization' => [
                'token' => $token,
                'type' => 'bearer',
                'expires_in' => $this->guard()->factory()->getTTL() * 60,
            ],
        ], 'Token refreshed successfully');
    }

    /**
     * Invalidate/revoke the current JWT token (Logout).
     */
    public function logout(): JsonResponse
    {
        $this->guard()->logout();

        return ApiResponse::success(null, 'Successfully logged out');
    }
}
