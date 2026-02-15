<?php

namespace Coderstm\Http\Middleware;

use Closure;
use Coderstm\Services\Theme;
use Illuminate\Http\Request;

class RequestThemeMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->filled('theme')) {
            Theme::set($request->theme);
        }

        return $next($request);
    }
}
