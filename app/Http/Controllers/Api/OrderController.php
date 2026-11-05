<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        return OrderResource::collection($request->user('api')->orders()->with(['items', 'payments' => fn ($query) => $query->latest()])->latest()->get());
    }
}
