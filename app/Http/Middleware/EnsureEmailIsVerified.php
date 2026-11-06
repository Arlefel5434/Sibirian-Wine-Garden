<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('api');

        if ($user instanceof User && ! $user->hasVerifiedEmail()) {
            throw new AuthenticationException('Please confirm your email address before continuing.');
        }

        return $next($request);
    }
}
