<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminOrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class AdminOrderController extends Controller
{
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        Gate::forUser($request->user('api'))->authorize('viewAdministration', Order::class);

        return AdminOrderResource::collection(
            Order::query()
                ->with(['user.profile', 'payments' => fn ($query) => $query->latest()])
                ->withCount('items')
                ->latest()
                ->paginate(25),
        );
    }
}
