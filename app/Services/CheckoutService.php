<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CheckoutRequest;
use App\Models\Order;
use App\Models\Sku;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private readonly WalletService $wallets,
        private readonly OutboxService $outbox,
        private readonly ContentCache $cache,
    ) {}

    public function checkout(User $user, array $delivery, string $idempotencyKey): Order
    {
        $requestHash = hash('sha256', json_encode($delivery, JSON_THROW_ON_ERROR));

        $order = DB::transaction(function () use ($user, $delivery, $idempotencyKey, $requestHash) {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $previous = CheckoutRequest::query()
                ->with(['order.items', 'order.payments'])
                ->where('user_id', $user->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($previous) {
                if (! hash_equals($previous->request_hash, $requestHash)) {
                    throw ValidationException::withMessages(['idempotency_key' => 'This idempotency key was already used with different checkout data.']);
                }

                return $previous->order;
            }

            $cart = Cart::query()->where('user_id', $user->id)->lockForUpdate()->first();

            if (! $cart || ! $cart->items()->exists()) {
                throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
            }

            $items = $cart->items()->orderBy('id')->get();
            $skus = Sku::query()
                ->with(['product.attributeValues.attribute'])
                ->whereKey($items->pluck('sku_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $currency = null;
            $total = 0;

            foreach ($items as $item) {
                $sku = $skus->get($item->sku_id);
                $this->ensureAvailable($sku, $item->quantity);
                $currency ??= $sku->currency;

                if ($currency !== $sku->currency) {
                    throw ValidationException::withMessages(['cart' => 'All items must use the same currency.']);
                }

                $total += $sku->price * $item->quantity;
            }

            $order = $user->orders()->create([
                'status' => 'pending',
                'total_amount' => $total,
                'currency' => $currency,
                'delivery_info' => $delivery,
            ]);

            foreach ($items as $item) {
                $sku = $skus->get($item->sku_id);
                $order->items()->create([
                    'sku_id' => $sku->id,
                    'quantity' => $item->quantity,
                    'price_at_purchase' => $sku->price,
                    'product_name' => $sku->product->name,
                    'sku_code' => $sku->sku,
                    'variant_name' => $sku->name,
                    'variant_attributes' => $sku->product->attributeValues->mapWithKeys(fn ($value) => [$value->attribute->name => $value->value])->all(),
                    'currency' => $sku->currency,
                    'quantity_unit' => $sku->quantity_unit,
                ]);
                $sku->decrement('stock_quantity', $item->quantity);
            }

            $payment = $order->payments()->create([
                'provider' => 'wallet',
                'amount' => $total,
                'currency' => $currency,
                'idempotency_key' => $idempotencyKey,
            ]);

            $this->wallets->reserve($user, $order, $payment);

            CheckoutRequest::query()->create([
                'user_id' => $user->id,
                'order_id' => $order->id,
                'idempotency_key' => $idempotencyKey,
                'request_hash' => $requestHash,
            ]);

            $this->outbox->record('OrderCreated', Order::class, $order->id, ['order_id' => $order->id]);
            $cart->items()->delete();

            return $order->load(['items', 'payments']);
        });

        $this->cache->invalidateProducts();

        return $order;
    }

    private function ensureAvailable(?Sku $sku, int $quantity): void
    {
        if (! $sku || $sku->product->status !== 'active') {
            throw ValidationException::withMessages(['cart' => 'This product is unavailable.']);
        }

        if ($sku->stock_quantity < $quantity) {
            throw ValidationException::withMessages(['quantity' => "Only {$sku->stock_quantity} items are available."]);
        }
    }
}
