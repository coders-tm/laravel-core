<?php

namespace Coderstm\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Stevebauman\Location\Facades\Location;

class ResolveIpAddress
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. Resolve IP (Covering Cloudflare headers if behind proxy)
        $ip = $request->header('CF-Connecting-IP') ?? $request->ip();

        // 2. Resolve & Cache Location
        if ($ip) {
            try {
                // Cache for 24 hours (86400 seconds)
                $location = Cache::remember("location.{$ip}", 86400, function () use ($ip) {
                    return Location::get($ip);
                });

                if ($location) {
                    // 3. Inject into request attributes
                    $request->attributes->set('ip_location', $location);

                    // Optional: also merge into input if needed, but attributes are safer
                    // $request->merge(['ip_location' => $location]);
                }
            } catch (\Throwable $e) {
                // Silently fail if location service is down
            }
        }

        return $next($request);
    }
}
