<?php

namespace Coderstm\Http\Middleware;

use Closure;
use Coderstm\Services\Theme;
use Illuminate\Http\Request;

class ThemeMiddleware
{
    public function handle(Request $request, Closure $next, string $theme)
    {
        Theme::set($theme);

        return $next($request);
    }
}
