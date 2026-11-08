<?php

namespace App\Services;

use App\Models\DeliverySubmission;
use App\Models\Order;

class DeliveryService
{
    public function submit(Order $order): DeliverySubmission
    {
        return DeliverySubmission::query()->updateOrCreate(
            ['order_id' => $order->id],
            [
                'provider' => 'demo',
                'status' => 'submitted',
                'provider_reference' => 'demo-order-'.$order->id,
                'payload' => [
                    'order_id' => $order->id,
                    'delivery' => $order->delivery_info,
                    'items' => $order->items->map(fn ($item) => [
                        'sku' => $item->sku_code,
                        'quantity' => $item->quantity,
                    ])->all(),
                ],
                'submitted_at' => now(),
                'last_error' => null,
            ],
        );
    }
}
