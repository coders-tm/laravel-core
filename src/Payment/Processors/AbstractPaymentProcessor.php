<?php

namespace Coderstm\Payment\Processors;

use Coderstm\Contracts\PaymentProcessorInterface;
use Coderstm\Models\Payment;
use Coderstm\Models\PaymentMethod;
use Coderstm\Payment\CallbackResult;
use Coderstm\Payment\Payable;
use Coderstm\Payment\RefundResult;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

abstract class AbstractPaymentProcessor implements PaymentProcessorInterface
{
    protected $paymentMethod = null;

    protected $payable = null;

    abstract public function getProvider(): string;

    public function supportedCurrencies(): array
    {
        return [];
    }

    public function handleSuccessCallback(Request $request): CallbackResult
    {
        return CallbackResult::success(message: 'Payment completed successfully!');
    }

    public function handleCancelCallback(Request $request): CallbackResult
    {
        return CallbackResult::success(message: 'Payment was cancelled. You can try again or choose a different payment method.');
    }

    protected function getSuccessUrl(array $params = []): string
    {
        if ($url = $this->payable?->getSuccessCallbackUrl($params)) {
            return $url;
        }

        return route('payment.success', array_merge($params, ['provider' => $this->getProvider()]));
    }

    protected function getCancelUrl(array $params = []): string
    {
        if ($url = $this->payable?->getCancelCallbackUrl($params)) {
            return $url;
        }

        return route('payment.cancel', array_merge($params, ['provider' => $this->getProvider()]));
    }

    protected function getWebhookUrl(): string
    {
        return app_url("/api/{$this->getProvider()}/webhook");
    }

    public function setPaymentMethod(PaymentMethod $paymentMethod): PaymentProcessorInterface
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getPaymentMethod(): ?PaymentMethod
    {
        return $this->paymentMethod;
    }

    public function getPaymentMethodId(): ?int
    {
        return $this->paymentMethod ? $this->paymentMethod->id : null;
    }

    public function setPayable(Payable $payable): PaymentProcessorInterface
    {
        $this->payable = $payable;

        return $this;
    }

    public function getPayable(): ?Payable
    {
        return $this->payable;
    }

    public function refund(Payment $payment, ?float $amount = null, ?string $reason = null): RefundResult
    {
        RefundResult::notSupported("Refund is not supported for the {$this->getProvider()} payment provider");
    }

    public function supportsRefund(): bool
    {
        return false;
    }

    public function validateCurrency(Payable $payable): void
    {
        $currency = $payable->getCurrency();
        $supportedCurrencies = $this->supportedCurrencies();
        if (empty($supportedCurrencies)) {
            return;
        }
        if (! in_array(strtoupper($currency), $supportedCurrencies)) {
            throw ValidationException::withMessages(['currency' => "The currency {$currency} is not supported by {$this->getProvider()}."]);
        }
    }
}
