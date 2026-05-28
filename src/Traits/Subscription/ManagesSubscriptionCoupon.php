<?php

namespace Coderstm\Traits\Subscription;

use Coderstm\Coderstm;
use Coderstm\Models\Shop\Order\DiscountLine;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait ManagesSubscriptionCoupon
{
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coderstm::$couponModel);
    }

    public function withCoupon($coupon): self
    {
        $couponModel = Coderstm::$couponModel;
        if ($coupon = $couponModel::findByCode($coupon)) {
            $this->coupon()->associate($coupon);
        }

        return $this;
    }

    public function canApplyCoupon($coupon = null)
    {
        $coupon = $coupon ?? $this->coupon;
        $foreignKey = $this->getUserForeignKey();
        $userId = $this->{$foreignKey};
        if ($coupon && $coupon->canApplyToPlan($this->plan)) {
            if ($coupon->duration->value === 'once') {
                if ($coupon->redeems()->where($foreignKey, $userId)->exists()) {
                    return null;
                }
            }
            if ($coupon->duration->value === 'repeating') {
                if ($coupon->redeems()->where($foreignKey, $userId)->count() >= $coupon->duration_in_months) {
                    return null;
                }
            }

            return $coupon;
        }

        return null;
    }

    protected function discount()
    {
        if ($coupon = $this->canApplyCoupon()) {
            $discountType = match ($coupon->discount_type) {
                'percentage' => DiscountLine::TYPE_PERCENTAGE,
                'fixed' => DiscountLine::TYPE_FIXED_AMOUNT,
                'override' => DiscountLine::TYPE_PRICE_OVERRIDE,
                default => DiscountLine::TYPE_PERCENTAGE,
            };

            return ['type' => $discountType, 'value' => $coupon->value, 'description' => $coupon->name, 'coupon_id' => $coupon->id, 'coupon_code' => $coupon->promotion_code, 'auto_applied' => false];
        }

        return null;
    }
}
