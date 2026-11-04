<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\StoreCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartItemController extends Controller
{
    public function store(StoreCartItemRequest $request, CartService $cart): JsonResponse
    {
        return $this->response($request, $cart->add($request->user('api'), $request->integer('sku_id'), $request->integer('quantity')));
    }

    public function update(UpdateCartItemRequest $request, int $cartItem, CartService $cart): JsonResponse
    {
        return $this->response($request, $cart->update($request->user('api'), $cartItem, $request->integer('quantity')));
    }

    public function destroy(Request $request, int $cartItem, CartService $cart): JsonResponse
    {
        return $this->response($request, $cart->remove($request->user('api'), $cartItem));
    }

    private function response(Request $request, Cart $cart): JsonResponse
    {
        return response()->json(['data' => CartResource::make($cart)->resolve($request)]);
    }
}
