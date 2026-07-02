<?php

namespace Coderstm\Traits;

use Coderstm\Coderstm;

trait Orderable
{
    public function orders()
    {
        return $this->hasMany(Coderstm::$orderModel, 'customer_id');
    }

    public function latestOrder()
    {
        return $this->hasOne(Coderstm::$orderModel, 'customer_id')->orderBy('created_at', 'desc');
    }
}
