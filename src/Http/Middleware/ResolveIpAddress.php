<?php

namespace Coderstm\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Stevebauman\Location\Facades\Location;

class ResolveIpAddress
{
    public function handle(Request $request, Closure $next)
    {
        $ip = $request->header('CF-Connecting-IP') ?? $request->ip();
        if ($ip) {
            try {
                $location = Cache::remember("location.{$ip}", 86400, function () use ($ip) {
                    return Location::get($ip);
                });
                if ($location) {
                    $request->attributes->set('ip_location', $location);
                }
            } catch (\Throwable $e) {
            }
        }

        return $next($request);
    }
}
