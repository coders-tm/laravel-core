<?php

namespace Coderstm\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class GuardMiddleware
{
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;
        if (guard(...$guards)) {
            return $next($request);
        }

        return $this->failed();
    }

    protected function failed()
    {
        if (request()->expectsJson()) {
            return response()->json(['message' => __('Unauthenticated.')], 401);
        }

        return redirect()->guest('/login');
    }
}
