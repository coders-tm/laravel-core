<?php

namespace Coderstm\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string|null detect()
 * @method static string|null resolveGuardFromModel($user)
 *
 * @see \Coderstm\Services\Guard
 */
class Guard extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'coderstm.guard';
    }
}
