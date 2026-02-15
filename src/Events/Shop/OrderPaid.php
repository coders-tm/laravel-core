<?php

namespace Coderstm\Events\Shop;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderPaid
{
    use Dispatchable, SerializesModels;

    public $order;

    public function __construct($order)
    {
        $this->order = $order->fresh(['customer', 'line_items', 'payments']);
    }
}
