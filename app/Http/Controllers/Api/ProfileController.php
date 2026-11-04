<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function __invoke(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user('api');
        $data = $request->validated();

        DB::transaction(function () use ($user, $data) {
            if ($user->email !== $data['email']) {
                $user->forceFill(['email' => $data['email'], 'email_verified_at' => null])->save();
            }

            $user->profile()->update(['phone_number' => $data['phone_number'] ?? null]);
        });

        return response()->json([
            'data' => UserResource::make($user->refresh()->load('profile'))->resolve($request),
        ]);
    }
}
