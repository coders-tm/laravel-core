<?php

namespace Coderstm\Models;

use Coderstm\Traits\Core;
use Laravel\Cashier\Cashier;
use Coderstm\Enum\CouponDuration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Coupon extends Model
{
    use Core;

    protected $fillable = [
        'name',
        'promotion_code',
        'currency',
        'duration',
        'duration_in_months',
        'max_redemptions',
        'percent_off',
        'amount_off',
        'fixed',
        'active',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'active' => 'boolean',
        'fixed' => 'boolean',
        'duration' => CouponDuration::class,
    ];

    protected $withCount = ['redeems'];

    protected $appends = ['has_max_redemptions', 'specific_plans', 'has_expires_at'];

    protected function hasExpiresAt(): Attribute
    {
        return Attribute::make(
            get: fn () => !is_null($this->expires_at),
        );
    }

    protected function hasMaxRedemptions(): Attribute
    {
        return Attribute::make(
            get: fn () => !is_null($this->max_redemptions),
        );
    }

    protected function specificPlans(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->plans->count() > 0,
        );
    }

    protected function currency(): Attribute
    {
        return Attribute::make(
            set: fn () => config('cashier.currency'),
        );
    }

    public function redeems(): HasMany
    {
        return $this->hasMany(Redeem::class);
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'coupon_plans', 'coupon_id', 'plan_id');
    }

    public function syncPlans(array $items = [])
    {
        $this->plans()->sync(collect($items)->pluck('id'));
        return $this;
    }

    public function scopeOnlyActive($query)
    {
        return $query->where('active', 1);
    }

    public static function create(array $attributes = [])
    {
        try {
            // create a coupon in gateway
            $stripeCoupon = static::createStripeCoupon($attributes);
            $promotionCode = static::createStripePromo($stripeCoupon->id, $attributes);

            // Call the parent create method to save the model
            $coupon = (new static)->fill(
                collect($attributes)
                    ->only((new static)->getFillable())
                    ->toArray()
            );

            $coupon->stripe_id = $stripeCoupon->id;
            $coupon->promotion_id = $promotionCode->id;
            $coupon->save();

            return $coupon;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function canApplyToPlan($plan): bool
    {
        if ($this->specific_plans) {
            return (bool) $this->plans()->whereIn('id', [$plan])->count();
        }
        return true;
    }

    public function checkRaxRedemptions(): bool
    {
        if ($this->max_redemptions) {
            return $this->redeems_count >= $this->max_redemptions;
        }
        return false;
    }

    public function toPublic()
    {
        return $this->only([
            'name',
            'promotion_code',
            'currency',
            'duration',
            'duration_in_months',
            'percent_off',
            'amount_off',
            'fixed',
        ]);
    }

    public function hasStripeId()
    {
        return !is_null($this->stripe_id);
    }

    public function hasPromotionId()
    {
        return !is_null($this->promotion_id);
    }

    public function createAsStripeCoupon()
    {
        if (!$this->hasStripeId()) {
            $coupon = static::createStripeCoupon($this->toArray());
            $promotionCode = static::createStripePromo($coupon->id, $this->toArray());
            $this->stripe_id = $coupon->id;
            $this->promotion_id = $promotionCode->id;
            $this->save();
        }
        return $this;
    }

    protected static function findByCode($couponCode)
    {
        return static::onlyActive()
            ->where('promotion_code', $couponCode)
            ->first();
    }

    protected static function createStripePromo(string $coupon, array $attributes = [])
    {
        $optional = optional((object) $attributes);
        $args = collect([
            'coupon' => $coupon,
            'code' => $optional->promotion_code,
            'max_redemptions' => $optional->max_redemptions,
            'active' => $optional->active,
        ])->filter();
        return Cashier::stripe()->promotionCodes->create($args->all());
    }

    protected static function createStripeCoupon(array $attributes = [])
    {
        $optional = optional((object) $attributes);
        $args = collect([
            'name' => $optional->name,
            'currency' => config('cashier.currency'),
            'duration' => $optional->duration,
            'duration_in_months' => $optional->duration_in_months,
            'max_redemptions' => $optional->max_redemptions,
            'amount_off' => $optional->amount_off,
            'percent_off' => $optional->percent_off,
        ])->filter();
        return Cashier::stripe()->coupons->create($args->all());
    }

    protected static function booted()
    {
        parent::booted();
        static::updated(function ($model) {
            if ($model->hasStripeId()) {
                Cashier::stripe()->coupons->update($model->stripe_id, $model->only([
                    'name',
                ]));
            }
        });
    }
}
