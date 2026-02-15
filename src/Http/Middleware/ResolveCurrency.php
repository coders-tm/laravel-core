<?php

namespace Coderstm\Http\Middleware;

use Closure;
use Coderstm\Facades\Currency;
use Illuminate\Http\Request;

class ResolveCurrency
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $baseCurrency = config('app.currency', 'USD');
        if (app()->environment('local')) {
            Currency::initialize('INR');

            return $next($request);
        }
        if ($user && ! empty($user->currency)) {
            Currency::initialize($user->currency);

            return $next($request);
        }
        $address = [];
        if ($user) {
            if (! $user->relationLoaded('address')) {
                $user->load('address');
            }
            if ($user->address) {
                $address = ['country_code' => $user->address->country_code ?? null, 'country' => $user->address->country ?? null];
            }
        }
        $currencyService = Currency::resolve($address);
        if ($user && $user->address && ! $user->currency) {
            $resolvedCode = $currencyService->code();
            if ($resolvedCode !== $baseCurrency) {
                $user->updateQuietly(['currency' => $resolvedCode]);
            }
        }

        return $next($request);
    }
}
