<?php

namespace Coderstm\Traits\Subscription;

use Coderstm\Models\Coupon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait ManagesSubscriptionCoupon
{
    /**
     * Get coupon relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Apply a coupon to the subscription.
     *
     * @param  string  $coupon
     * @return $this
     */
    public function withCoupon($coupon): self
    {
        if ($coupon = Coupon::findByCode($coupon)) {
            $this->coupon()->associate($coupon);
        }

        return $this;
    }

    /**
     * Check if coupon can be applied to subscription.
     *
     * @param  \Coderstm\Models\Coupon|null  $coupon
     * @return \Coderstm\Models\Coupon|null
     */
    public function canApplyCoupon(?Coupon $coupon = null): ?Coupon
    {
        $coupon = $coupon ?? $this->coupon;
        $foreignKey = $this->getUserForeignKey();
        $userId = $this->{$foreignKey};

        if ($coupon && $coupon->canApply($this->plan)) {
            // if coupon duration is once, we will check if the user has already used the coupon
            if ($coupon->duration->value === 'once') {
                if ($coupon->redeems()->where($foreignKey, $userId)->exists()) {
                    return null;
                }
            }

            // if coupon duration is repeating, we will check if the user has already used the coupon
            if ($coupon->duration->value === 'repeating') {
                if ($coupon->redeems()->where($foreignKey, $userId)->count() >= $coupon->duration_in_months) {
                    return null;
                }
            }

            return $coupon;
        }

        return null;
    }

    /**
     * Get discount from active coupon.
     *
     * @return array|null
     */
    protected function discount()
    {
        // Check if coupon exists
        if ($coupon = $this->canApplyCoupon()) {
            return [
                'type' => $coupon->fixed ? 'fixed_amount' : 'percentage',
                'value' => $coupon->fixed ? $coupon->amount_off : $coupon->percent_off,
                'description' => $coupon->name,
            ];
        }
        return null;
    }
}
