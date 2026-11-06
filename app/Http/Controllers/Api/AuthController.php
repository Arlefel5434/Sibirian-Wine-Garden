<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResendEmailVerificationRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Auth\JwtAuthService;
use App\Services\Auth\RefreshTokenService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $connection = User::query()->getModel()->getConnection();
        $user = $connection->transaction(
            fn (): User => User::query()->create($request->validated()),
            3,
        );

        event(new Registered($user));

        return response()->json([
            'message' => 'Account created. Check your email and follow the confirmation link before signing in.',
            'email' => $user->email,
        ], Response::HTTP_CREATED);
    }

    public function resendVerification(ResendEmailVerificationRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->validated('email'))->first();

        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return response()->json([
            'message' => 'If this address belongs to an unverified account, a new confirmation link has been sent.',
        ]);
    }

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
