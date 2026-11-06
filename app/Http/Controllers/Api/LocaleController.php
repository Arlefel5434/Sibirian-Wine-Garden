<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class LocaleController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $supported = collect(config('localization.supported'))
            ->map(fn (array $locale, string $code) => [
                'code' => $code,
                'name' => $locale['name'],
                'native_name' => $locale['native_name'],
            ])
            ->values();

        return response()->json([
            'current' => app()->getLocale(),
            'supported' => $supported,
        ]);
    }
}
