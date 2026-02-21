<?php

namespace Coderstm\Events\Shop;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentFailed
{
    use Dispatchable, SerializesModels;

    public $order;

    public $payment;

    public $reason;

    public function __construct($order, $payment = null, $reason = null)
    {
        $this->order = $order;
        $this->payment = $payment;
        $this->reason = $reason;
    }
}
