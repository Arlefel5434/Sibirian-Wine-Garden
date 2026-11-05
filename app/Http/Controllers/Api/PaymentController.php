<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function store(StorePaymentRequest $request, Order $order, PaymentService $payments): PaymentResource
    {
        return PaymentResource::make($payments->create(
            $request->user('api'),
            $order,
            $request->string('provider')->toString(),
            $request->string('idempotency_key')->toString(),
        ));
    }

    public function show(Request $request, Payment $payment): PaymentResource
    {
        abort_unless($payment->order()->where('user_id', $request->user('api')->id)->exists(), 404);

        return PaymentResource::make($payment);
    }
}
