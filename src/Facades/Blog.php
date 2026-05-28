<?php

namespace Coderstm\Facades;

use Illuminate\Support\Facades\Facade;

class Blog extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'blog';
    }
}
