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
    /**
     * The payment method instance
     *
     * @var PaymentMethod|null
     */
    protected $paymentMethod = null;

    /**
     * The payable instance
     *
     * @var Payable|null
     */
    protected $payable = null;

    /**
     * Get the payment provider name (must be implemented by child classes)
     */
    abstract public function getProvider(): string;

    /**
     * Get the list of supported currencies.
     * Return empty array to support all currencies.
     */
    public function supportedCurrencies(): array
    {
        return [];
    }

    /**
     * Default implementation for success callback
     * Returns success result - controller handles redirect
     * Override this in child classes for provider-specific behavior
     */
    public function handleSuccessCallback(Request $request): CallbackResult
    {
        return CallbackResult::success(
            message: 'Payment completed successfully!'
        );
    }

    /**
     * Default implementation for cancel callback
     * Returns success result - controller handles redirect
     * Override this in child classes for provider-specific behavior
     */
    public function handleCancelCallback(Request $request): CallbackResult
    {
        return CallbackResult::success(
            message: 'Payment was cancelled. You can try again or choose a different payment method.'
        );
    }

    /**
     * Get the success URL for this payment processor.
     * Uses a custom successCallbackUrl from the payable when set.
     * Falls back to the named route for payment success.
     */
    protected function getSuccessUrl(array $params = []): string
    {
        if ($url = $this->payable?->getSuccessCallbackUrl($params)) {
            return $url;
        }

        return route('payment.success', array_merge($params, [
            'provider' => $this->getProvider(),
        ]));
    }

    /**
     * Get the cancel URL for this payment processor.
     * Uses a custom cancelCallbackUrl from the payable when set.
     * Falls back to the named route for payment cancel.
     */
    protected function getCancelUrl(array $params = []): string
    {
        if ($url = $this->payable?->getCancelCallbackUrl($params)) {
            return $url;
        }

        return route('payment.cancel', array_merge($params, [
            'provider' => $this->getProvider(),
        ]));
    }

    /**
     * Get the webhook URL for this payment processor
     */
    protected function getWebhookUrl(): string
    {
        return app_url("/api/{$this->getProvider()}/webhook");
    }

    /**
     * Set the payment method for this processor
     */
    public function setPaymentMethod(PaymentMethod $paymentMethod): PaymentProcessorInterface
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    /**
     * Get the payment method for this processor
     */
    public function getPaymentMethod(): ?PaymentMethod
    {
        return $this->paymentMethod;
    }

    /**
     * Get the payment method ID for this processor
     */
    public function getPaymentMethodId(): ?int
    {
        return $this->paymentMethod ? $this->paymentMethod->id : null;
    }

    /**
     * Set the payable for this processor
     */
    public function setPayable(Payable $payable): PaymentProcessorInterface
    {
        $this->payable = $payable;

        return $this;
    }

    /**
     * Get the payable for this processor
     */
    public function getPayable(): ?Payable
    {
        return $this->payable;
    }

    /**
     * Process a refund for a payment.
     *
     * Default implementation throws "not supported" exception.
     * Override in child classes for gateway-specific refund logic.
     *
     * @param  Payment  $payment  The payment to refund
     * @param  float|null  $amount  Amount to refund (null = full refund)
     * @param  string|null  $reason  Reason for the refund
     */
    public function refund(Payment $payment, ?float $amount = null, ?string $reason = null): RefundResult
    {
        RefundResult::notSupported(
            "Refund is not supported for the {$this->getProvider()} payment provider"
        );
    }

    /**
     * Check if this payment processor supports refunds.
     *
     * Override in child classes that implement refund functionality.
     */
    public function supportsRefund(): bool
    {
        return false;
    }

    /**
     * Validate that the payable currency is supported by this processor.
     *
     * @throws ValidationException
     */
    public function validateCurrency(Payable $payable): void
    {
        $currency = $payable->getCurrency();
        $supportedCurrencies = $this->supportedCurrencies();

        // If supported currencies list is empty, it means all currencies are supported
        if (empty($supportedCurrencies)) {
            return;
        }

        if (! in_array(strtoupper($currency), $supportedCurrencies)) {
            throw ValidationException::withMessages([
                'currency' => "The currency {$currency} is not supported by {$this->getProvider()}.",
            ]);
        }
    }
}
