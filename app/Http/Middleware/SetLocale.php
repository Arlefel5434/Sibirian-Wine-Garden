<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('localization.supported'));
        $requested = $request->query('lang')
            ?? $request->cookie('locale')
            ?? $request->header('X-Locale')
            ?? config('localization.default');
        $locale = in_array($requested, $supported, true) ? $requested : config('localization.fallback');

        App::setLocale($locale);

        $response = $next($request);
        $response->headers->set('Content-Language', $locale);
        $response->headers->set('Vary', 'Cookie, X-Locale');

        if ($request->query->has('lang')) {
            $response->headers->setCookie(cookie()->forever('locale', $locale, '/', null, $request->isSecure(), false, false, 'lax'));
        }

        return $response;
    }
}
