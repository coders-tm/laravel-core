<?php

namespace Coderstm\Models\Plan;

use Coderstm\Models\Plan;
use Laravel\Cashier\Cashier;
use Coderstm\Enum\PlanInterval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Price extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'plan_prices';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'plan_id',
        'stripe_id',
        'interval',
        'interval_count',
        'amount',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'interval' => PlanInterval::class,
    ];

    /**
     * Get the plan that owns the Price
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get all of the features for the Plan
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function features(): HasMany
    {
        return $this->hasMany(Feature::class, 'plan_id', 'plan_id');
    }

    /**
     * Determine if the plan has a payment gateway id.
     *
     * @return bool
     */
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
