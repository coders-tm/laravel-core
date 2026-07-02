<?php

namespace Coderstm\Facades;

use Illuminate\Support\Facades\Facade;

class Guard extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'coderstm.guard';
    }
}
