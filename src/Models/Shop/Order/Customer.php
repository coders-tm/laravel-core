<?php

namespace Coderstm\Models\Shop\Order;

use Coderstm\Models\Shop\Customer as ShopCustomer;

class Customer extends ShopCustomer
{
    protected $table = 'users';

    protected $appends = ['name'];

    protected $with = [];
}
