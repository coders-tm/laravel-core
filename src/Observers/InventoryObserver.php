<?php

namespace Coderstm\Observers;

use Coderstm\Events\Shop\LowStockAlert;
use Coderstm\Events\Shop\OutOfStockAlert;
use Coderstm\Models\Shop\Product\Inventory;

class InventoryObserver
{
    protected $lowStockThreshold = 10;

    public function updated(Inventory $inventory)
    {
        if (! $inventory->trackable) {
            return;
        }
        if ($inventory->wasChanged('available')) {
            $previousStock = $inventory->getOriginal('available');
            $currentStock = $inventory->available;
            if ($previousStock > 0 && $currentStock == 0) {
                event(new OutOfStockAlert($inventory->variant, $inventory));
            } elseif ($currentStock > 0 && $currentStock <= $this->lowStockThreshold && $previousStock > $this->lowStockThreshold) {
                event(new LowStockAlert($inventory->variant, $inventory, $this->lowStockThreshold));
            }
        }
    }

    public function created(Inventory $inventory)
    {
        if (! $inventory->trackable) {
            return;
        }
        if ($inventory->available == 0) {
            event(new OutOfStockAlert($inventory->variant, $inventory));
        } elseif ($inventory->available <= $this->lowStockThreshold) {
            event(new LowStockAlert($inventory->variant, $inventory, $this->lowStockThreshold));
        }
    }
}
