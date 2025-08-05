<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Coderstm\Models\Shop\Order;
use Coderstm\Payment\Processor;
use Coderstm\Models\PaymentMethod;
use Coderstm\Models\Shop\Checkout;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Coderstm\Repository\CartRepository;

class ShopController extends Controller
{
    /**
     * Calculate order totals based on line items
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function calculator(Request $request)
    {
        $request->merge([
            'line_items' => $request->line_items ?? [],
        ]);

        $order = Order::firstOrNew(['id' => $request->id], []);

        // Use CartRepository to process request and calculate order totals
        $order = CartRepository::fromRequest($request, $order);

        return response()->json($order, 200);
    }

    /**
     * Unified Setup Payment Intent
     * Handles payment setup for all providers using the factory pattern
     *
     * @param Request $request
     * @param string $provider
     */
    public function setupPaymentIntent(Request $request, string $provider)
    {
        $request->validate([
            'checkout_token' => 'required|string',
            'amount' => 'sometimes|numeric',
            'currency' => 'sometimes|string',
        ]);

        try {
            $checkout = Checkout::where('token', $request->checkout_token)->firstOrFail();
            $paymentMethod = PaymentMethod::where('provider', $provider)->where('active', true)->firstOrFail();

            // Add amount and currency to request if not provided
            $request->merge([
                'amount' => $request->amount ?? $checkout->grand_total,
                'currency' => $request->currency ?? ($checkout->currency ?? config('app.currency', 'USD')),
            ]);

            $provider = $paymentMethod->integration_via ?? $provider;

            // Check if provider is supported
            if (!Processor::isSupported($provider)) {
                return response()->json([
                    'message' => 'Payment method not supported',
                    'provider' => $provider,
                ], 422);
            }

            // Create processor using factory
            $processor = Processor::make($provider);
            $result = $processor->setupPaymentIntent($request, $checkout);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Payment setup failed: ' . $e->getMessage(),
                'provider' => $provider,
            ], 500);
        }
    }

    /**
     * Unified Confirm Payment
     * Handles payment confirmation for all providers using the factory pattern
     */
    public function confirmPayment(Request $request, string $provider)
    {
        $request->validate([
            'checkout_token' => 'required|string',
        ]);

        try {
            $checkout = Checkout::where('token', $request->checkout_token)->firstOrFail();
            $paymentMethod = PaymentMethod::where('provider', $provider)->where('active', true)->firstOrFail();
            $provider = $paymentMethod->integration_via ?? $provider;

            // Check if provider is supported
            if (!Processor::isSupported($provider)) {
                return response()->json([
                    'message' => 'Payment method not supported',
                    'provider' => $provider,
                ], 422);
            }

            // Create processor using factory
            $processor = Processor::make($provider);
            $result = $processor->confirmPayment($request, $checkout);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Payment confirmation failed',
                'message' => $e->getMessage(),
                'provider' => $provider,
            ], 500);
        }
    }

    /**
     * Handle checkout success redirect from payment providers
     * Uses Processor for unified callback handling
     */
    public function handleCheckoutSuccess(Request $request, string $provider)
    {
        try {
            // Use the factory to handle the success callback
            $result = Processor::handleSuccessCallback($provider, $request);

            // Use result redirect and message
            $redirectUrl = $result['redirect_url'] ?? '/user/shop/cart';
            $message = $result['message'] ?? 'Payment completed successfully!';
            $messageType = $result['success'] ? 'success' : 'error';

            return redirect($redirectUrl)->with($messageType, $message);
        } catch (\Exception $e) {
            // Log the error but don't show it to user
            Log::error("Checkout success handler error for provider {$provider}: " . $e->getMessage(), [
                'request' => $request->all(),
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            // Fallback to cart redirect
            return redirect('/user/shop/cart')
                ->with('info', 'Payment may have been completed. Please check your orders or contact support if needed.');
        }
    }

    /**
     * Handle checkout cancellation from payment providers
     * Uses Processor for unified callback handling
     */
    public function handleCheckoutCancel(Request $request, string $provider)
    {
        try {
            // Use the factory to handle the cancel callback
            $result = Processor::handleCancelCallback($provider, $request);

            // Use result redirect and message
            $redirectUrl = $result['redirect_url'] ?? '/user/shop/checkout';
            $message = $result['message'] ?? 'Payment was cancelled. You can try again or choose a different payment method.';
            $messageType = $result['success'] ? 'warning' : 'error';

            return redirect($redirectUrl)->with($messageType, $message);
        } catch (\Exception $e) {
            // Log the error but don't show it to user
            Log::error("Checkout cancel handler error for provider {$provider}: " . $e->getMessage(), [
                'request' => $request->all(),
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            // Fallback to checkout redirect
            return redirect('/user/shop/checkout')
                ->with('info', 'Payment process was interrupted. Please try again.');
        }
    }
}
