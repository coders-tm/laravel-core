<?php

namespace Coderstm\Payment\Processors;

use Coderstm\Contracts\PaymentProcessorInterface;
use Coderstm\Models\Payment;
use Coderstm\Payment\CallbackResult;
use Coderstm\Payment\Payable;
use Coderstm\Payment\RefundResult;
use Illuminate\Http\Request;

abstract class AbstractPaymentProcessor implements PaymentProcessorInterface
{
    protected $paymentMethod = null;

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
        $url = app_url("/payment/{$this->getProvider()}/success");
        if (! empty($params)) {
            $url .= '?'.http_build_query($params);
        }

        return $url;
    }

    protected function getCancelUrl(array $params = []): string
    {
        $url = app_url("/payment/{$this->getProvider()}/cancel");
        if (! empty($params)) {
            $url .= '?'.http_build_query($params);
        }

        return $url;
    }

    protected function getWebhookUrl(): string
    {
        return app_url("/api/{$this->getProvider()}/webhook");
    }

    public function setPaymentMethod(\Coderstm\Models\PaymentMethod $paymentMethod): \Coderstm\Contracts\PaymentProcessorInterface
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getPaymentMethod(): ?\Coderstm\Models\PaymentMethod
    {
        return $this->paymentMethod;
    }

    public function getPaymentMethodId(): ?int
    {
        return $this->paymentMethod ? $this->paymentMethod->id : null;
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
            throw \Illuminate\Validation\ValidationException::withMessages(['currency' => "The currency {$currency} is not supported by {$this->getProvider()}."]);
        }
    }
}
