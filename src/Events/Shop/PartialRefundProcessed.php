<?php

namespace Coderstm\Events\Shop;

use Coderstm\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PartialRefundProcessed
{
    use Dispatchable, SerializesModels;

    public $order;

    public $payment;

    public $amount;

    public $reason;

    public function __construct($order, Payment $payment, float $amount, $reason = null)
    {
        $this->order = $order;
        $this->payment = $payment;
        $this->amount = $amount;
        $this->reason = $reason;
    }
}
