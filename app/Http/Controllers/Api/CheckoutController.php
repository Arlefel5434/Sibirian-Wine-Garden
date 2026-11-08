<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Services\CheckoutService;

class CheckoutController extends Controller
{
    public function __invoke(CheckoutRequest $request, CheckoutService $checkout): OrderResource
    {
        return new OrderResource($checkout->checkout(
            $request->user('api'),
            $request->safe()->except('idempotency_key'),
            $request->string('idempotency_key')->toString(),
        ));
    }
}
