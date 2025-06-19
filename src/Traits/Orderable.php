<?php

namespace Coderstm\Traits;

use Coderstm\Models\Shop\Order;

trait Orderable
{
    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function latestOrder()
    {
        return $this->hasOne(Order::class, 'customer_id')
            ->orderBy('created_at', 'desc');
    }
}
