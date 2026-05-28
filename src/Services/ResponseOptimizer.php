<?php

namespace Coderstm\Services;

use Closure;
use Coderstm\Contracts\ConfigurationInterface;
use Illuminate\Http\Request;

class ResponseOptimizer
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        try {
            $loader = app(ConfigurationInterface::class);

            return $loader->optimizeResponse($request, $response);
        } catch (\Throwable $e) {
        }

        return $response;
    }
}
