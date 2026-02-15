<?php

namespace Coderstm\Events\Checkout;

use Coderstm\Models\Shop\Checkout;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Abandoned
{
    use Dispatchable, SerializesModels;

    public Checkout $cart;

    public function __construct(Checkout $cart)
    {
        $this->cart = $cart;
    }
}
