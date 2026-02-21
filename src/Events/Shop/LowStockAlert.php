<?php

namespace Coderstm\Events\Shop;

use Coderstm\Models\Shop\Product\Variant;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LowStockAlert
{
    use Dispatchable, SerializesModels;

    public $variant;

    public $inventory;

    public $threshold;

    public function __construct(Variant $variant, $inventory = null, int $threshold = 10)
    {
        $this->variant = $variant;
        $this->inventory = $inventory;
        $this->threshold = $threshold;
    }
}
