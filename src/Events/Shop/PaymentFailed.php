<?php

namespace Coderstm\Events\Shop;

use Coderstm\Models\Payment;
use Coderstm\Models\Shop\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentFailed
{
    use Dispatchable, SerializesModels;

    /**
     * The order instance.
     *
     * @var Order
     */
    public $order;

    public $payment;

    public $reason;

    /**
     * Create a new event instance.
     *
     * @param  Order  $order
     * @param  Payment|null  $payment
     * @param  string|null  $reason
     * @return void
     */
    public function __construct($order, $payment = null, $reason = null)
    {
        $this->order = $order;
        $this->payment = $payment;
        $this->reason = $reason;
    }
}
