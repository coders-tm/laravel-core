<?php

namespace Coderstm\Jobs\Shop;

use Coderstm\Models\Shop\Location;
use Coderstm\Models\Shop\Product\Inventory;
use Coderstm\Models\Shop\Product\Variant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateInventoryForLocation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Location $location;

    public function __construct(Location $location)
    {
        $this->location = $location;
    }

    public function handle(): void
    {
        Variant::chunkById(100, function ($items) {
            foreach ($items as $item) {
                Inventory::updateOrCreate(['active' => true, 'variant_id' => $item->id, 'location_id' => $this->location->id]);
            }
        });
    }
}
