<?php

namespace Coderstm\Payment\Processors;

use Coderstm\Contracts\PaymentProcessorInterface;
use Coderstm\Exceptions\PaymentException;
use Coderstm\Models\PaymentMethod;
use Coderstm\Payment\Payable;
use Coderstm\Payment\PaymentResult;
use Illuminate\Http\Request;

class ManualProcessor extends AbstractPaymentProcessor implements PaymentProcessorInterface
{
    private const SUPPORTED_CURRENCIES = [];

    public function getProvider(): string
    {
        return PaymentMethod::MANUAL;
    }

    public function supportedCurrencies(): array
    {
        return self::SUPPORTED_CURRENCIES;
    }

    public function setupPaymentIntent(Request $request, Payable $payable): array
    {
        // Manual payments don't need a setup intent
        return [
            'message' => 'Manual payment ready for processing',
            'amount' => $payable->getGrandTotal(),
            'currency' => strtoupper(config('app.currency', 'USD')),
        ];
    }

    public function confirmPayment(Request $request, Payable $payable): PaymentResult
    {
        throw new PaymentException('This payment method is not supported for online payments. Please contact support to complete your payment.');
    }
}
