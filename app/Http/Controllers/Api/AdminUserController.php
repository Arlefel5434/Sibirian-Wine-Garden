<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\AdminUserResource;
use App\Models\User;
use App\Services\AdminUserService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class AdminUserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::forUser($request->user('api'))->authorize('viewAdministration', User::class);

        return AdminUserResource::collection(
            User::query()
                ->with(['profile', 'ban'])
                ->withCount('orders')
                ->latest()
                ->paginate(25),
        );
    }

    public function update(UpdateUserRequest $request, User $user, AdminUserService $users): AdminUserResource
    {
        return AdminUserResource::make(
            $users->update($user, $request->user('api'), $request->validated()),
        );
    }
}
