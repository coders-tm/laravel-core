<?php

namespace Coderstm\Events\Shop;

use Coderstm\Models\Payment;
use Coderstm\Models\Shop\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentSuccessful
{
    use Dispatchable, SerializesModels;

    /**
     * The order instance.
     *
     * @var Order
     */
    public $order;

    public $payment;

    /**
     * Create a new event instance.
     *
     * @param  Order  $order
     * @param  Payment|null  $payment
     * @return void
     */
    public function __construct($order, $payment = null)
    {
        $this->order = $order;
        $this->payment = $payment;
    }
}
