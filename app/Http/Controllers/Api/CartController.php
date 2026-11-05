<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __invoke(Request $request, CartService $cart): JsonResponse
    {
        return response()->json(['data' => CartResource::make($cart->get($request->user('api')))->resolve($request)]);
    }
}
