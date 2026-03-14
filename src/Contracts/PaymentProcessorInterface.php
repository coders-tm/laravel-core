<?php

namespace Coderstm\Contracts;

use Coderstm\Models\Payment;
use Coderstm\Models\PaymentMethod;
use Coderstm\Payment\CallbackResult;
use Coderstm\Payment\Payable;
use Coderstm\Payment\PaymentResult;
use Coderstm\Payment\RefundResult;
use Illuminate\Http\Request;

interface PaymentProcessorInterface
{
    public function setupPaymentIntent(Request $request, Payable $payable): array;

    public function confirmPayment(Request $request, Payable $payable): PaymentResult;

    public function handleSuccessCallback(Request $request): CallbackResult;

    public function handleCancelCallback(Request $request): CallbackResult;

    public function getProvider(): string;

    public function supportedCurrencies(): array;

    public function validateCurrency(Payable $payable): void;

    public function setPaymentMethod(PaymentMethod $paymentMethod): PaymentProcessorInterface;

    public function getPaymentMethod(): ?PaymentMethod;

    public function refund(Payment $payment, ?float $amount = null, ?string $reason = null): RefundResult;

    public function supportsRefund(): bool;
}
