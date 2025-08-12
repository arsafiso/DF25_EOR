<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckPasswordReset
{
    public function handle($request, Closure $next)
    {
        if (Auth::check() && Auth::user()->force_password_reset) {
            return response()->json(['message' => __('messages.force_password_reset')], 403);
        }

        return $next($request);
    }
}

