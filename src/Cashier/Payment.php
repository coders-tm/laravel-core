<?php

namespace Coderstm\Cashier;

use Coderstm\Cashier\Exceptions\IncompletePayment;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Traits\ForwardsCalls;
use JsonSerializable;
use Stripe\PaymentIntent as StripePaymentIntent;

class Payment implements Arrayable, Jsonable, JsonSerializable
{
    use ForwardsCalls;

    public function __construct(protected StripePaymentIntent $paymentIntent) {}

    public function amount(): string
    {
        return Cashier::formatAmount($this->rawAmount(), $this->paymentIntent->currency);
    }

    public function rawAmount(): int
    {
        return $this->paymentIntent->amount;
    }

    public function clientSecret(): string
    {
        return $this->paymentIntent->client_secret;
    }

    public function capture(array $options = [])
    {
        return $this->paymentIntent->capture($options);
    }

    public function requiresPaymentMethod(): bool
    {
        return $this->paymentIntent->status === StripePaymentIntent::STATUS_REQUIRES_PAYMENT_METHOD;
    }

    public function requiresAction(): bool
    {
        return $this->paymentIntent->status === StripePaymentIntent::STATUS_REQUIRES_ACTION;
    }

    public function requiresConfirmation(): bool
    {
        return $this->paymentIntent->status === StripePaymentIntent::STATUS_REQUIRES_CONFIRMATION;
    }

    public function requiresCapture(): bool
    {
        return $this->paymentIntent->status === StripePaymentIntent::STATUS_REQUIRES_CAPTURE;
    }

    public function cancel(array $options = [])
    {
        return $this->paymentIntent->cancel($options);
    }

    public function isCanceled(): bool
    {
        return $this->paymentIntent->status === StripePaymentIntent::STATUS_CANCELED;
    }

    public function isSucceeded(): bool
    {
        return $this->paymentIntent->status === StripePaymentIntent::STATUS_SUCCEEDED;
    }

    public function isProcessing(): bool
    {
        return $this->paymentIntent->status === StripePaymentIntent::STATUS_PROCESSING;
    }

    public function validate(): void
    {
        if ($this->requiresPaymentMethod()) {
            throw IncompletePayment::paymentMethodRequired($this);
        } elseif ($this->requiresAction()) {
            throw IncompletePayment::requiresAction($this);
        } elseif ($this->requiresConfirmation()) {
            throw IncompletePayment::requiresConfirmation($this);
        }
    }

    public function asStripePaymentIntent(array $expand = [])
    {
        if ($expand) {
            return $this->customer()->stripe()->paymentIntents->retrieve($this->paymentIntent->id, ['expand' => $expand]);
        }

        return $this->paymentIntent;
    }

    public function refresh(array $expand = [])
    {
        $this->paymentIntent = $this->asStripePaymentIntent($expand);

        return $this;
    }

    public function toArray()
    {
        return $this->asStripePaymentIntent()->toArray();
    }

    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function __get($key)
    {
        return $this->paymentIntent->{$key};
    }

    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->paymentIntent, $method, $parameters);
    }
}
