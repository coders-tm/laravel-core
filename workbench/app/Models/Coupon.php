<?php

namespace Workbench\App\Models;

use Coderstm\Models\Coupon as Base;
use Workbench\Database\Factories\CouponFactory;

class Coupon extends Base
{
    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return CouponFactory::new();
    }
}
