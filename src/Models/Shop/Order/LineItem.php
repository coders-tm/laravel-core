<?php

namespace Coderstm\Models\Shop\Order;

use Coderstm\Contracts\Currencyable;
use Coderstm\Traits\Core;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class LineItem extends Model implements Currencyable
{
    use Core;

    protected $hidden = ['itemable_type', 'itemable_id'];

    protected $fillable = ['title', 'price', 'quantity', 'taxable', 'metadata', 'is_custom'];

    protected $with = ['discount'];

    protected $appends = ['discounted_price', 'has_discount', 'total'];

    protected $casts = ['metadata' => 'json', 'taxable' => 'boolean', 'is_custom' => 'boolean'];

    public function itemable()
    {
        return $this->morphTo();
    }

    public function getCurrencyFields(): array
    {
        return ['price', 'total', 'discounted_price'];
    }

    public function discount()
    {
        return $this->morphOne(DiscountLine::class, 'discountable');
    }

    public function hasDiscount(): bool
    {
        return ! is_null($this->discount) ?: false;
    }

    protected function total(): Attribute
    {
        return Attribute::make(get: fn () => round($this->discounted_price * $this->quantity, 2));
    }

    protected function discountedPrice(): Attribute
    {
        return Attribute::make(get: function () {
            if ($this->hasDiscount()) {
                return $this->discount->calculateFinalPrice($this->price);
            }

            return $this->price;
        });
    }

    public function getHasDiscountAttribute()
    {
        return $this->hasDiscount();
    }
}
