<?php

namespace Coderstm\Contracts;

use Illuminate\Http\Request;
use Coderstm\Models\Shop\Checkout;

interface PaymentProcessorInterface
{
    /**
     * Setup payment intent for the provider
     */
    public function setupPaymentIntent(Request $request, Checkout $checkout): array;

    /**
     * Confirm payment for the provider
     */
    public function confirmPayment(Request $request, Checkout $checkout): array;

    /**
     * Handle successful payment callback
     * Default implementation redirects to cart
     */
    public function handleSuccessCallback(Request $request): array;

    /**
     * Handle payment cancellation callback
     * Default implementation redirects to checkout
     */
    public function handleCancelCallback(Request $request): array;

    /**
     * Get the provider name
     */
    public function getProvider(): string;
}
