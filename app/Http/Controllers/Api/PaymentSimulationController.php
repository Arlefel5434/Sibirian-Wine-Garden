<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SimulatePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\PaymentService;

class PaymentSimulationController extends Controller
{
    public function __invoke(SimulatePaymentRequest $request, Payment $payment, PaymentService $payments): PaymentResource
    {
        return PaymentResource::make($payments->simulate(
            $request->user('api'),
            $payment,
            $request->string('outcome')->toString(),
        ));
    }
}
