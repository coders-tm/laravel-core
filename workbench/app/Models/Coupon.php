<?php

namespace App\Models;

use Coderstm\Models\Coupon as Base;
use Database\Factories\CouponFactory;

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
