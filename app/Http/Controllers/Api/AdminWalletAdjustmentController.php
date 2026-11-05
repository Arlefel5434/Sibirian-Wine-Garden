<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustWalletRequest;
use App\Http\Resources\WalletResource;
use App\Models\User;
use App\Services\WalletService;

class AdminWalletAdjustmentController extends Controller
{
    public function __invoke(AdjustWalletRequest $request, User $user, WalletService $wallets): WalletResource
    {
        return WalletResource::make($wallets->adjust(
            $user,
            $request->string('direction')->toString(),
            $request->integer('amount'),
            $request->user('api'),
            $request->string('reason')->toString(),
        ));
    }
}
