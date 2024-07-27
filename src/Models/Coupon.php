<?php

namespace Coderstm\Models;

use Coderstm\Traits\Core;
use Coderstm\Enum\CouponDuration;
use Coderstm\Models\Subscription\Plan;
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

    protected static function findByCode($couponCode)
    {
        return static::onlyActive()
            ->where('promotion_code', $couponCode)
            ->first();
    }
}
