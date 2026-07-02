<?php

namespace Coderstm\Cashier\Exceptions;

use Coderstm\Cashier\Payment;
use Exception;
use Throwable;

class IncompletePayment extends Exception
{
    public $payment;

    public function __construct(Payment $payment, string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->payment = $payment;
    }

    public static function paymentMethodRequired(Payment $payment)
    {
        return new static($payment, 'The payment attempt failed because of an invalid payment method.');
    }

    public static function requiresAction(Payment $payment)
    {
        return new static($payment, 'The payment attempt failed because additional action is required before it can be completed.');
    }

    public static function requiresConfirmation(Payment $payment)
    {
        return new static($payment, 'The payment attempt failed because it needs to be confirmed before it can be completed.');
    }
}
