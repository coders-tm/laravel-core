<?php

namespace Coderstm\Models\Shop\Order;

use Coderstm\Coderstm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountLine extends Model implements \Coderstm\Contracts\Currencyable
{
    public function getCurrencyFields(): array
    {
        if ($this->isPercentage()) {
            return [];
        }

        return ['value'];
    }

    use HasFactory;

    public $timestamps = false;

    const TYPE_PERCENTAGE = 'percentage';

    const TYPE_FIXED_AMOUNT = 'fixed_amount';

    const TYPE_PRICE_OVERRIDE = 'price_override';

    protected $fillable = ['type', 'value', 'description', 'coupon_id', 'coupon_code'];

    protected $casts = ['value' => 'decimal:2', 'coupon_id' => 'integer'];

    protected $hidden = ['discountable_type', 'discountable_id'];

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

    public function calculateDiscountAmount(float $basePrice): float
    {
        return match ($this->type) {
            self::TYPE_PERCENTAGE => round($basePrice * ($this->value / 100), 2),
            self::TYPE_FIXED_AMOUNT => min($this->value, $basePrice),
            self::TYPE_PRICE_OVERRIDE => max(0, $basePrice - $this->value),
            default => 0,
        };
    }

    public function calculateFinalPrice(float $basePrice): float
    {
        return match ($this->type) {
            self::TYPE_PERCENTAGE => $basePrice - $this->calculateDiscountAmount($basePrice),
            self::TYPE_FIXED_AMOUNT => max(0, $basePrice - $this->value),
            self::TYPE_PRICE_OVERRIDE => $this->value,
            default => $basePrice,
        };
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_PERCENTAGE => 'Percentage',
            self::TYPE_FIXED_AMOUNT => 'Fixed Amount',
            self::TYPE_PRICE_OVERRIDE => 'Price Override',
            default => 'Unknown',
        };
    }

    public function getFormattedValue(): string
    {
        return match ($this->type) {
            self::TYPE_PERCENTAGE => $this->value.'%',
            self::TYPE_FIXED_AMOUNT => '$'.number_format($this->value, 2),
            self::TYPE_PRICE_OVERRIDE => '$'.number_format($this->value, 2),
            default => (string) $this->value,
        };
    }

    public function discountable()
    {
        return $this->morphTo();
    }

    public function coupon()
    {
        return $this->belongsTo(Coderstm::$couponModel);
    }

    public static function fromCoupon($coupon, float $basePrice = 0): self
    {
        $discountLine = new self;
        $discountLine->type = match ($coupon->discount_type) {
            'percentage' => self::TYPE_PERCENTAGE,
            'fixed' => self::TYPE_FIXED_AMOUNT,
            'override' => self::TYPE_PRICE_OVERRIDE,
            default => self::TYPE_PERCENTAGE,
        };
        $discountLine->value = $coupon->value;
        $discountLine->description = $coupon->name;
        $discountLine->coupon_id = $coupon->id;
        $discountLine->coupon_code = $coupon->promotion_code;

        return $discountLine;
    }
}
