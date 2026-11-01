<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\JwtAuthService;
use App\Services\Auth\RefreshTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(
        LoginRequest $request,
        JwtAuthService $auth,
        RefreshTokenService $refreshTokens,
    ): JsonResponse {
        $token = $auth->login($request->validated());
        $refresh = $refreshTokens->issue($auth->user(), $request);

        return response()->json([
            ...$token,
            'user' => new UserResource($auth->user()),
        ])->withCookie($refreshTokens->cookie($refresh));
    }

    public function me(JwtAuthService $auth): JsonResponse
    {
        return response()->json([
            'data' => UserResource::make($auth->user())->resolve(),
        ]);
    }

    public function refresh(
        Request $request,
        JwtAuthService $auth,
        RefreshTokenService $refreshTokens,
    ): JsonResponse {
        $session = $refreshTokens->rotate(
            (string) $request->cookie($refreshTokens->cookieName()),
            $request,
        );

        return response()->json([
            ...$auth->tokenFor($session['user']),
            'user' => new UserResource($session['user']->loadMissing('profile')),
        ])->withCookie($refreshTokens->cookie($session['refresh']));
    }

    public function logout(
        Request $request,
        JwtAuthService $auth,
        RefreshTokenService $refreshTokens,
    ): JsonResponse {
        $refreshTokens->revoke($request->cookie($refreshTokens->cookieName()));
        $auth->logout();

        return response()
            ->json(['message' => 'Signed out successfully.'])
            ->withCookie($refreshTokens->forgetCookie());
    }
}
