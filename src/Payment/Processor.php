<?php

namespace Coderstm\Payment;

use Illuminate\Http\Request;
use Coderstm\Payment\Processors\StripeProcessor;
use Coderstm\Payment\Processors\RazorpayProcessor;
use Coderstm\Payment\Processors\PaypalProcessor;
use Coderstm\Payment\Processors\KlarnaProcessor;
use Coderstm\Payment\Processors\ManualProcessor;
use Coderstm\Payment\Processors\MercadoPagoProcessor;
use Coderstm\Payment\Processors\XenditProcessor;
use Coderstm\Payment\Processors\PaystackProcessor;
use Coderstm\Payment\Processors\FlutterwaveProcessor;
use Coderstm\Contracts\PaymentProcessorInterface;

class Processor
{
    /**
     * Create a payment processor instance for the given provider
     */
    public static function make(string $provider): PaymentProcessorInterface
    {
        return match ($provider) {
            'stripe' => new StripeProcessor(),
            'razorpay' => new RazorpayProcessor(),
            'paypal' => new PaypalProcessor(),
            'klarna' => new KlarnaProcessor(),
            'manual' => new ManualProcessor(),
            'mercadopago' => new MercadoPagoProcessor(),
            'xendit' => new XenditProcessor(),
            'paystack' => new PaystackProcessor(),
            'flutterwave' => new FlutterwaveProcessor(),
            default => throw new \InvalidArgumentException("Unsupported payment provider: {$provider}")
        };
    }

    /**
     * Get all supported payment providers
     */
    public static function getSupportedProviders(): array
    {
        return [
            'stripe',
            'razorpay',
            'paypal',
            'klarna',
            'manual',
            'mercadopago',
            'xendit',
            'paystack',
            'flutterwave',
        ];
    }

    /**
     * Check if a provider is supported
     */
    public static function isSupported(string $provider): bool
    {
        return in_array($provider, self::getSupportedProviders());
    }

    /**
     * Handle success callback for a provider
     */
    public static function handleSuccessCallback(string $provider, Request $request): array
    {
        if (!self::isSupported($provider)) {
            return [
                'success' => false,
                'redirect_url' => '/user/shop/cart',
                'message' => 'Unsupported payment provider'
            ];
        }

        try {
            $processor = self::make($provider);
            return $processor->handleSuccessCallback($request);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'redirect_url' => '/user/shop/cart',
                'message' => 'Error processing payment callback'
            ];
        }
    }

    /**
     * Handle cancel callback for a provider
     */
    public static function handleCancelCallback(string $provider, Request $request): array
    {
        if (!self::isSupported($provider)) {
            return [
                'success' => false,
                'redirect_url' => '/user/shop/checkout',
                'message' => 'Unsupported payment provider'
            ];
        }

        try {
            $processor = self::make($provider);
            return $processor->handleCancelCallback($request);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'redirect_url' => '/user/shop/checkout',
                'message' => 'Error processing payment cancellation'
            ];
        }
    }
}
