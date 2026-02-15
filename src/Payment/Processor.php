<?php

namespace Coderstm\Payment;

use Coderstm\Contracts\PaymentProcessorInterface;
use Coderstm\Models\PaymentMethod;
use Coderstm\Payment\Processors\AlipayProcessor;
use Coderstm\Payment\Processors\FlutterwaveProcessor;
use Coderstm\Payment\Processors\KlarnaProcessor;
use Coderstm\Payment\Processors\ManualProcessor;
use Coderstm\Payment\Processors\MercadoPagoProcessor;
use Coderstm\Payment\Processors\PaypalProcessor;
use Coderstm\Payment\Processors\PaystackProcessor;
use Coderstm\Payment\Processors\RazorpayProcessor;
use Coderstm\Payment\Processors\StripeProcessor;
use Coderstm\Payment\Processors\WalletProcessor;
use Coderstm\Payment\Processors\XenditProcessor;
use Illuminate\Http\Request;

class Processor
{
    public static function make(string $provider): PaymentProcessorInterface
    {
        return match ($provider) {
            'stripe' => new StripeProcessor,
            'razorpay' => new RazorpayProcessor,
            'paypal' => new PaypalProcessor,
            'klarna' => new KlarnaProcessor,
            'manual' => new ManualProcessor,
            'wallet' => new WalletProcessor,
            'mercadopago' => new MercadoPagoProcessor,
            'xendit' => new XenditProcessor,
            'paystack' => new PaystackProcessor,
            'flutterwave' => new FlutterwaveProcessor,
            'alipay' => new AlipayProcessor,
            default => throw new \InvalidArgumentException("Unsupported payment provider: {$provider}"),
        };
    }

    public static function getSupportedProviders(): array
    {
        return ['stripe', 'razorpay', 'paypal', 'klarna', 'manual', 'wallet', 'mercadopago', 'xendit', 'paystack', 'flutterwave', 'alipay'];
    }

    public static function isSupported(string $provider): bool
    {
        return in_array($provider, self::getSupportedProviders());
    }

    public static function handleSuccessCallback(string $provider, Request $request): CallbackResult
    {
        if (! self::isSupported($provider)) {
            return CallbackResult::failed(message: 'Unsupported payment provider');
        }
        try {
            $paymentMethod = PaymentMethod::byProvider($provider);
            $processor = self::make($provider);
            $processor->setPaymentMethod($paymentMethod);

            return $processor->handleSuccessCallback($request);
        } catch (\Throwable $e) {
            return CallbackResult::failed(message: 'Error processing payment callback: '.$e->getMessage());
        }
    }

    public static function handleCancelCallback(string $provider, Request $request): CallbackResult
    {
        if (! self::isSupported($provider)) {
            return CallbackResult::failed(message: 'Unsupported payment provider');
        }
        try {
            $paymentMethod = PaymentMethod::byProvider($provider);
            $processor = self::make($provider);
            $processor->setPaymentMethod($paymentMethod);

            return $processor->handleCancelCallback($request);
        } catch (\Throwable $e) {
            return CallbackResult::failed(message: 'Error processing payment cancellation: '.$e->getMessage());
        }
    }
}
