<?php

namespace Coderstm\Events\Shop;

use Coderstm\Models\Payment;
use Coderstm\Models\Shop\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderRefunded
{
    use Dispatchable, SerializesModels;

    /**
     * The order instance.
     *
     * @var Order
     */
    public $order;

    public $payment;

    public $amount;

    public $reason;

    /**
     * Create a new event instance.
     *
     * @param  Order  $order
     * @param  string|null  $reason
     * @return void
     */
    public function __construct($order, Payment $payment, float $amount, $reason = null)
    {
        $this->order = $order;
        $this->payment = $payment;
        $this->amount = $amount;
        $this->reason = $reason;
    }
}
