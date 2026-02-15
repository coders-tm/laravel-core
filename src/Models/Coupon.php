<?php

namespace Coderstm\Models;

use Coderstm\Enum\CouponDuration;
use Coderstm\Models\Subscription\Plan;
use Coderstm\Traits\Core;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use Core;

    protected $fillable = ['name', 'promotion_code', 'type', 'duration', 'duration_in_months', 'max_redemptions', 'value', 'discount_type', 'active', 'expires_at', 'auto_apply'];

    protected $casts = ['expires_at' => 'datetime', 'active' => 'boolean', 'auto_apply' => 'boolean', 'value' => 'decimal:2', 'duration' => CouponDuration::class];

    protected $withCount = ['redeems'];

    protected $appends = ['has_max_redemptions', 'specific_plans', 'specific_products', 'has_expires_at'];

    const TYPE_PLAN = 'plan';

    const TYPE_PRODUCT = 'product';

    const DISCOUNT_TYPE_PERCENTAGE = 'percentage';

    const DISCOUNT_TYPE_FIXED = 'fixed';

    const DISCOUNT_TYPE_OVERRIDE = 'override';

    protected function hasExpiresAt(): Attribute
    {
        return Attribute::make(get: fn () => ! is_null($this->expires_at));
    }

    protected function hasMaxRedemptions(): Attribute
    {
        return Attribute::make(get: fn () => ! is_null($this->max_redemptions));
    }

    protected function specificPlans(): Attribute
    {
        return Attribute::make(get: fn () => $this->plans->count() > 0);
    }

    protected function specificProducts(): Attribute
    {
        return Attribute::make(get: fn () => $this->products->count() > 0);
    }

    protected function currency(): Attribute
    {
        return Attribute::make(set: fn () => config('cashier.currency'));
    }

    public function redeems(): HasMany
    {
        return $this->hasMany(Redeem::class);
    }

    public function redemptions(): HasMany
    {
        return $this->redeems();
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'coupon_plans', 'coupon_id', 'plan_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany('Coderstm\\Models\\Shop\\Product', 'coupon_products', 'coupon_id', 'product_id');
    }

    public function syncPlans(array $items = [])
    {
        $this->plans()->sync(collect($items)->pluck('id'));

        return $this;
    }

    public function syncProducts(array $items = [])
    {
        $this->products()->sync(collect($items)->pluck('id'));

        return $this;
    }

    public function scopeOnlyActive($query)
    {
        return $query->where('active', 1);
    }

    public function scopeAutoApplicable($query)
    {
        return $query->onlyActive()->where('auto_apply', true)->where(function ($q) {
            $q->whereNull('max_redemptions')->orWhereRaw('(select count(*) from redeems where coupons.id = redeems.coupon_id) < max_redemptions');
        })->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function canApply($item, string $type = self::TYPE_PLAN): bool
    {
        if (! $this->isActive() || $this->isExpired() || $this->checkMaxRedemptions()) {
            return false;
        }
        if ($type === self::TYPE_PLAN) {
            return $this->canApplyToPlan($item);
        } elseif ($type === self::TYPE_PRODUCT) {
            return $this->canApplyToProduct($item);
        }

        return false;
    }

    public function canApplyToPlan($plan): bool
    {
        $hasRestrictions = $this->plans()->exists();
        if (! $hasRestrictions && $this->type === self::TYPE_PLAN) {
            return true;
        }
        if (is_object($plan)) {
            $plan = $plan->id;
        }
        if ($plan && $this->plans()->where('plan_id', $plan)->exists()) {
            return true;
        }

        return false;
    }

    public function canApplyToProduct($product): bool
    {
        $hasRestrictions = $this->products()->exists();
        if (! $hasRestrictions && $this->type === self::TYPE_PRODUCT) {
            return true;
        }
        if (is_object($product)) {
            $product = $product->id;
        }
        if ($product && $this->products()->where('product_id', $product)->exists()) {
            return true;
        }

        return false;
    }

    public function checkMaxRedemptions(): bool
    {
        if ($this->max_redemptions) {
            return $this->redeems_count >= $this->max_redemptions;
        }

        return false;
    }

    public function getAmount($amount): float
    {
        if ($this->discount_type === 'override' && $this->value !== null) {
            return max(0, $amount - $this->value);
        }
        if ($this->discount_type === 'fixed') {
            return $this->value;
        }

        return round($amount * ($this->value / 100), 2);
    }

    public function getFinalPrice($originalPrice): float
    {
        if ($this->discount_type === 'override' && $this->value !== null) {
            return $this->value;
        }
        $discountAmount = $this->getAmount($originalPrice);

        return max(0, $originalPrice - $discountAmount);
    }

    public function getDiscountPriority($basePrice): float
    {
        return $this->getAmount($basePrice);
    }

    public function toPublic()
    {
        return $this->only(['name', 'promotion_code', 'currency', 'duration', 'duration_in_months', 'value', 'discount_type']);
    }

    public static function findByCode($couponCode)
    {
        return static::onlyActive()->where('promotion_code', $couponCode)->first();
    }

    public static function newFactory()
    {
        return \Coderstm\Database\Factories\CouponFactory::new();
    }
}
