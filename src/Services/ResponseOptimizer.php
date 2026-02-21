<?php

namespace Coderstm\Services;

use Closure;
use Illuminate\Http\Request;

class ResponseOptimizer
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        try {
            $loader = app(\Coderstm\Contracts\ConfigurationInterface::class);

            return $loader->optimizeResponse($request, $response);
        } catch (\Throwable $e) {
        }

        return $response;
    }
}
