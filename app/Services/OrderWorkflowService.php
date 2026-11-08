<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderWorkflowStep;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class OrderWorkflowService
{
    public function complete(Order $order, string $step): void
    {
        $workflowStep = OrderWorkflowStep::query()
            ->where('order_id', $order->id)
            ->where('name', $step)
            ->lockForUpdate()
            ->firstOrFail();

        if ($workflowStep->status !== 'completed') {
            $workflowStep->update([
                'status' => 'completed',
                'completed_at' => now(),
                'failed_at' => null,
                'last_error' => null,
            ]);
        }

        $deliveryCompleted = OrderWorkflowStep::query()
            ->where('order_id', $order->id)
            ->where('name', 'delivery')
            ->where('status', 'completed')
            ->exists();

        if ($deliveryCompleted && $order->status === 'paid') {
            $order->update(['status' => 'processing']);
        }
    }

    public function fail(int $orderId, string $step, ?Throwable $exception): void
    {
        DB::transaction(function () use ($orderId, $step, $exception) {
            $workflowStep = OrderWorkflowStep::query()
                ->where('order_id', $orderId)
                ->where('name', $step)
                ->lockForUpdate()
                ->first();

            if (! $workflowStep || $workflowStep->status === 'completed') {
                return;
            }

            $workflowStep->update([
                'status' => 'failed',
                'failed_at' => now(),
                'last_error' => Str::limit($exception?->getMessage() ?? 'The queued job failed.', 2000, ''),
            ]);
        });
    }
}
