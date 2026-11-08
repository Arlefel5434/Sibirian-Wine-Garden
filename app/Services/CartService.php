<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Sku;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function get(User $user): Cart
    {
        return $user->cart()->firstOrCreate()->load(['items.sku.translations', 'items.sku.product.translations']);
    }

    public function add(User $user, int $skuId, int $quantity): Cart
    {
        return DB::transaction(function () use ($user, $skuId, $quantity) {
            $cart = $user->cart()->firstOrCreate();
            $sku = Sku::query()->with('product')->lockForUpdate()->findOrFail($skuId);
            $item = $cart->items()->where('sku_id', $sku->id)->first();
            $newQuantity = ($item?->quantity ?? 0) + $quantity;

            $this->ensureAvailable($sku, $newQuantity);
            $cart->items()->updateOrCreate(['sku_id' => $sku->id], ['quantity' => $newQuantity]);

            return $cart->load(['items.sku.translations', 'items.sku.product.translations']);
        });
    }

    public function update(User $user, int $itemId, int $quantity): Cart
    {
        return DB::transaction(function () use ($user, $itemId, $quantity) {
            $item = $this->ownedItem($user, $itemId);
            $sku = Sku::query()->with('product')->lockForUpdate()->findOrFail($item->sku_id);
            $this->ensureAvailable($sku, $quantity);
            $item->update(['quantity' => $quantity]);

            return $item->cart->load(['items.sku.translations', 'items.sku.product.translations']);
        });
    }

    public function remove(User $user, int $itemId): Cart
    {
        $item = $this->ownedItem($user, $itemId);
        $cart = $item->cart;
        $item->delete();

        return $cart->load(['items.sku.translations', 'items.sku.product.translations']);
    }

    private function ownedItem(User $user, int $itemId): CartItem
    {
        return CartItem::query()->whereKey($itemId)->whereHas('cart', fn ($query) => $query->where('user_id', $user->id))->firstOrFail();
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
