<?php

namespace Coderstm\Events\Shop;

use Coderstm\Models\Shop\Product\Variant;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OutOfStockAlert
{
    use Dispatchable, SerializesModels;

    public $variant;

    public $inventory;

    public function __construct(Variant $variant, $inventory = null)
    {
        $this->variant = $variant;
        $this->inventory = $inventory;
    }
}
