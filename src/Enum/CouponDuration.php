<?php

namespace Coderstm\Enum;

enum CouponDuration: string
{
    case FOREVER = 'forever';
    case ONCE = 'once';
    case REPEATING = 'repeating';
}
