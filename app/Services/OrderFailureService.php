<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Sku;
use Illuminate\Support\Facades\DB;

class OrderFailureService
{
    public function __construct(private readonly WalletService $wallets, private readonly ContentCache $cache) {}

    public function cancelPending(int $orderId): void
    {
        $changed = DB::transaction(function () use ($orderId) {
            $order = Order::query()->with(['items', 'payments'])->whereKey($orderId)->lockForUpdate()->first();

            if (! $order || $order->status !== 'pending') {
                return false;
            }

            $skuIds = $order->items->pluck('sku_id')->filter()->sort()->values();
            $skus = Sku::query()->whereKey($skuIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            foreach ($order->items as $item) {
                $skus->get($item->sku_id)?->increment('stock_quantity', $item->quantity);
            }

            $this->wallets->release($order);
            $order->payments()->where('status', 'pending')->update(['status' => 'failed']);
            $order->update(['status' => 'cancelled']);

            return true;
        });

        if ($changed) {
            $this->cache->invalidateProducts();
        }
    }
}
