<?php

namespace Coderstm\Facades;

use Coderstm\Services\ShopService;
use Illuminate\Support\Facades\Facade;

class Shop extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ShopService::class;
    }
}
