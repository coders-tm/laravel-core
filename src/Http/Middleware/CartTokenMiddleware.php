<?php

namespace Coderstm\Http\Middleware;

use Closure;
use Coderstm\Facades\Shop;
use Coderstm\Models\Shop\Checkout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class CartTokenMiddleware
{
    public function handle(Request $request, Closure $next): BaseResponse
    {
        try {
            $cartToken = Shop::token($request);
            if (! $cartToken) {
                $cartToken = Checkout::uuid('cart_token');
            }
        } catch (\Throwable $e) {
            $cartToken = null;
        }
        $response = $next($request);
        $this->setCartTokenCookie($response, $cartToken);

        return $response;
    }

    protected function setCartTokenCookie(BaseResponse $response, ?string $cartToken = null): void
    {
        if ($response instanceof JsonResponse || $response instanceof Response) {
            $response->cookie('cart_token', $cartToken, 60 * 24 * 365, '/', null, false, false);
        }
    }
}
