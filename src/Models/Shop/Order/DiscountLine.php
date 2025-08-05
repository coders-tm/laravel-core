<?php

namespace Coderstm\Models\Shop\Order;

use Coderstm\Models\Coupon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DiscountLine extends Model
{
    use HasFactory;

    public $timestamps = false;

    // Discount type constants
    const TYPE_PERCENTAGE = 'percentage';
    const TYPE_FIXED_AMOUNT = 'fixed_amount';
    const TYPE_PRICE_OVERRIDE = 'price_override';

    protected $fillable = [
        'type',
        'value',
        'description',
        'coupon_id',
        'coupon_code',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'coupon_id' => 'integer',
    ];

    protected $hidden = [
        'discountable_type',
        'discountable_id',
    ];

    public function isFixedAmount(): bool
    {
        return $this->type === self::TYPE_FIXED_AMOUNT;
    }

    public function isPercentage(): bool
    {
        return $this->type === self::TYPE_PERCENTAGE;
    }

    public function isPriceOverride(): bool
    {
        return $this->type === self::TYPE_PRICE_OVERRIDE;
    }

    /**
     * Calculate the discount amount for a given base price
     */
    public function calculateDiscountAmount(float $basePrice): float
    {
        return match ($this->type) {
            self::TYPE_PERCENTAGE => round($basePrice * ($this->value / 100), 2),
            self::TYPE_FIXED_AMOUNT => min($this->value, $basePrice), // Don't exceed the base price
            self::TYPE_PRICE_OVERRIDE => max(0, $basePrice - $this->value), // Difference between original and override price
            default => 0,
        };
    }

    /**
     * Calculate the final price after applying the discount
     */
    public function calculateFinalPrice(float $basePrice): float
    {
        return match ($this->type) {
            self::TYPE_PERCENTAGE => $basePrice - $this->calculateDiscountAmount($basePrice),
            self::TYPE_FIXED_AMOUNT => max(0, $basePrice - $this->value),
            self::TYPE_PRICE_OVERRIDE => $this->value, // Override price is the final price
            default => $basePrice,
        };
    }

    /**
     * Get the discount type in a human-readable format
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_PERCENTAGE => 'Percentage',
            self::TYPE_FIXED_AMOUNT => 'Fixed Amount',
            self::TYPE_PRICE_OVERRIDE => 'Price Override',
            default => 'Unknown',
        };
    }

    /**
     * Format the discount value for display
     */
    public function getFormattedValue(): string
    {
        return match ($this->type) {
            self::TYPE_PERCENTAGE => $this->value . '%',
            self::TYPE_FIXED_AMOUNT => '$' . number_format($this->value, 2),
            self::TYPE_PRICE_OVERRIDE => '$' . number_format($this->value, 2),
            default => (string) $this->value,
        };
    }

    public function discountable()
    {
        return $this->morphTo();
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Create a DiscountLine from a Coupon
     */
    public static function fromCoupon(Coupon $coupon, float $basePrice = 0): self
    {
        $discountLine = new self();

        // Map coupon discount_type to discount line type
        $discountLine->type = match ($coupon->discount_type) {
            'percentage' => self::TYPE_PERCENTAGE,
            'fixed' => self::TYPE_FIXED_AMOUNT,
            'override' => self::TYPE_PRICE_OVERRIDE,
            default => self::TYPE_PERCENTAGE
        };

        // Set the appropriate value based on discount type
        $discountLine->value = $coupon->value;

        $discountLine->description = $coupon->name;
        $discountLine->coupon_id = $coupon->id;
        $discountLine->coupon_code = $coupon->promotion_code;

        return $discountLine;
    }
}
