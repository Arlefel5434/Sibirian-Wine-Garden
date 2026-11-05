<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Services\CartService;

class CheckoutController extends Controller
{
    public function __invoke(CheckoutRequest $request, CartService $cart): OrderResource
    {
        return new OrderResource($cart->checkout($request->user('api'), $request->validated()));
    }
}
