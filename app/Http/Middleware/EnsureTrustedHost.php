<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts;
use Illuminate\Http\Request;

final class EnsureTrustedHost extends TrustHosts
{
    public function hosts()
    {
        return array_map(
            fn (string $host) => '^'.preg_quote($host, '/').'$',
            config('app.trusted_hosts'),
        );
    }

    public function handle(Request $request, $next)
    {
        return parent::handle($request, function (Request $request) use ($next) {
            $request->getHost();

            return $next($request);
        });
    }

    protected function shouldSpecifyTrustedHosts()
    {
        return true;
    }
}
