<?php

namespace Coderstm\Events\Checkout;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Completed
{
    use Dispatchable, SerializesModels;

    public $checkout;

    public $subscription;

    public function __construct($checkout, $subscription = null)
    {
        $this->checkout = $checkout;
        $this->subscription = $subscription;
    }
}
