<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WalletResource;
use App\Services\WalletService;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __invoke(Request $request, WalletService $wallets): WalletResource
    {
        return WalletResource::make($wallets->get($request->user('api')));
    }
}
