<?php

namespace Coderstm\Models\Plan;

use Coderstm\Models\Plan;
use Laravel\Cashier\Cashier;
use Coderstm\Enum\PlanInterval;
use Coderstm\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Price extends Model
{
    use HasFactory, SerializeDate;

    protected $table = 'plan_prices';

    protected $fillable = [
        'plan_id',
        'stripe_id',
        'interval',
        'interval_count',
        'amount',
    ];

    protected $casts = [
        'interval' => PlanInterval::class,
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function features(): HasMany
    {
        return $this->hasMany(Feature::class, 'plan_id', 'plan_id');
    }

    public function hasPaymentGatewayId()
    {
        return !is_null($this->stripe_id);
    }

    public static function planById($id, $interval = 'month')
    {
        $price = static::with('plan')->wherePlanId($id);
        if (in_array((string) $interval, ['month', 'year'])) {
            $price = $price->whereInterval($interval);
        }
        return $price->first();
    }

    public static function findByStripeId($id)
    {
        return static::firstWhere('stripe_id', $id);
    }

    public function createAsPaymentGatewayPrice()
    {
        if (!$this->hasPaymentGatewayId()) {
            $attributes = $this->toArray();
            $attributes['stripe_id'] = $this->plan->stripe_id;
            $price = static::createPrice($attributes);
            $this->stripe_id = $price->id;
            $this->save();
        }
        return $this;
    }

    protected static function createPrice(array $attributes = [])
    {
        $optional = optional((object) $attributes);
        return Cashier::stripe()->prices->create([
            'nickname' => $optional->label,
            'product' => $optional->stripe_id,
            'unit_amount' => $optional->amount * 100,
            'currency' => config('cashier.currency'),
            'recurring' => [
                'interval' => $optional->interval,
                'interval_count' => $optional->interval_count
            ],
        ]);
    }

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('default', function (Builder $builder) {
            $builder->withMax('plan as label', 'label');
        });
    }
}
