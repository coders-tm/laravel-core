<?php

namespace Coderstm\Models\Shop\Cart;

use Coderstm\Models\Shop\Order\DiscountLine;
use Coderstm\Models\Shop\Product;
use Coderstm\Models\Shop\Product\Variant;
use Illuminate\Database\Eloquent\Model;

class LineItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['id', 'price', 'quantity', 'taxable', 'discount', 'title', 'product_id', 'variant_title', 'variant_id', 'sku', 'is_custom', 'is_product_deleted', 'is_variant_deleted', 'metadata', 'plan_id'];

    protected $appends = ['thumbnail', 'discounted_price', 'has_discount', 'total', 'discount_amount'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant()
    {
        return $this->belongsTo(Variant::class, 'variant_id')->withOnly(['thumbnail']);
    }

    public function getDiscountAttribute($value)
    {
        return new DiscountLine($value ?: []);
    }

    public function getHasDiscountAttribute()
    {
        return ! is_null($this->discount) && ! empty($this->discount->value);
    }

    public function hasDiscount(): bool
    {
        return ! is_null($this->discount) && ! empty($this->discount->value);
    }

    public function getDiscountedPriceAttribute()
    {
        if ($this->hasDiscount()) {
            return $this->discount->calculateFinalPrice($this->price);
        }

        return $this->price;
    }

    public function getThumbnailAttribute($thumbnail = null)
    {
        if (isset($this->variant->thumbnail)) {
            return $this->variant->thumbnail;
        } elseif (isset($this->product->thumbnail)) {
            return $this->product->thumbnail;
        } else {
            return $thumbnail;
        }
    }

    public function getTotalAttribute()
    {
        return round($this->discounted_price * $this->quantity, 2);
    }

    public function getDiscountAmountAttribute()
    {
        if ($this->hasDiscount()) {
            return round($this->discount->calculateDiscountAmount($this->price) * $this->quantity, 2);
        }

        return 0;
    }
}
