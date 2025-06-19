<?php

namespace Coderstm\Services;

use Coderstm\Models\Shop\Order;
use Illuminate\Support\Facades\DB;
use Coderstm\Models\Subscription as Base;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Subscription extends Base
{
    public function orders(): MorphMany
    {
        return $this->morphMany(Order::class, 'orderable');
    }

    public function paid_orders(): MorphMany
    {
        return $this->morphMany(Order::class, 'orderable')->whereHasStatus('Paid');
    }

    protected static function booted(): void
    {
        static::addGlobalScope('reports', function (Builder $builder) {
            $builder->withMax('plan as plan_price', 'price');
            $builder->withMax('plan as plan_label', 'label');
            $builder->withSum('paid_orders as total_paid', 'grand_total');
            $builder->withCount([
                'user as user_name' => function (Builder $query) {
                    $query->select(DB::raw("CONCAT(users.first_name, ' ', users.last_name) as user_name"));
                },
            ]);
        });
    }
}
