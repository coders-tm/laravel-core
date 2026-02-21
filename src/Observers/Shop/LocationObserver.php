<?php

namespace Coderstm\Observers\Shop;

use Coderstm\Jobs\Shop\CreateInventoryForLocation;
use Coderstm\Jobs\Shop\UpdateInventoryForLocation;
use Coderstm\Models\Shop\Location;

class LocationObserver
{
    public function created(Location $location): void
    {
        CreateInventoryForLocation::dispatch($location)->afterResponse();
    }

    public function updated(Location $location): void
    {
        if ($location->wasChanged('active')) {
            UpdateInventoryForLocation::dispatch($location)->afterResponse();
        }
    }
}
