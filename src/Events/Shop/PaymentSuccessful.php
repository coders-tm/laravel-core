<?php

namespace Coderstm\Events\Shop;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentSuccessful
{
    use Dispatchable, SerializesModels;

    public $order;

    public $payment;

    public function __construct($order, $payment = null)
    {
        $this->order = $order;
        $this->payment = $payment;
    }
}
