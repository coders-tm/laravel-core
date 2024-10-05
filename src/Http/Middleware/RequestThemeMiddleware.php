<?php

namespace Coderstm\Http\Middleware;

use Closure;
use Coderstm\Services\Theme;
use Illuminate\Http\Request;

class RequestThemeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->filled('theme')) {
            Theme::set($request->theme);
        }

        return $next($request);
    }
}
