<?php

namespace Coderstm\Repository;

use Coderstm\Models\Shop\Cart\LineItem;
use Coderstm\Models\Shop\Order\DiscountLine;
use Coderstm\Models\Shop\Order\TaxLine;
use Coderstm\Rules\ArrayOrInstanceOf;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

abstract class BaseRepository extends Model
{
    public $timestamps = false;

    public $exists = false;

    protected $fillable = ['customer', 'line_items', 'billing_address', 'shipping_address', 'discount', 'tax_lines', 'collect_tax'];

    protected $appends = ['sub_total', 'tax_total', 'tax_lines', 'total_line_items', 'line_items_quantity', 'taxable_line_items', 'taxable_sub_total', 'discount_total', 'discount_per_item', 'taxable_discount', 'has_compound_tax', 'total_taxable', 'default_tax_total', 'shipping_total', 'grand_total'];

    protected $casts = ['collect_tax' => 'boolean'];

    protected Collection $taxes;

    public function __construct(array $attributes = [])
    {
        if (! isset($attributes['collect_tax'])) {
            $attributes['collect_tax'] = true;
        }
        $taxLines = $attributes['tax_lines'] ?? [];
        if (empty($taxLines)) {
            if (! empty($attributes['billing_address'])) {
                $taxLines = $this->getBillingAddressTax($attributes['billing_address']);
            } else {
                $taxLines = $this->getDefaultTax();
            }
            $attributes['tax_lines'] = $taxLines;
        }
        $this->validateAttributes($attributes);
        parent::__construct($attributes);
        foreach (['discount', 'line_items', 'tax_lines'] as $key) {
            if (array_key_exists($key, $attributes)) {
                $this->{$key} = $attributes[$key];
            }
        }
    }

    protected function getBillingAddressTax($billingAddress): ?array
    {
        if (function_exists('billing_address_tax')) {
            return billing_address_tax($billingAddress);
        }

        return [];
    }

    protected function getDefaultTax(): ?array
    {
        if (function_exists('default_tax')) {
            return default_tax();
        }

        return [];
    }

    public function rules(): array
    {
        return ['customer' => 'nullable|array', 'billing_address' => 'nullable|array', 'shipping_address' => 'nullable|array', 'line_items' => 'nullable|array', 'line_items.*' => [new ArrayOrInstanceOf(LineItem::class)], 'discount' => ['nullable', new ArrayOrInstanceOf(DiscountLine::class)], 'tax_lines' => 'nullable|array', 'tax_lines.*' => [new ArrayOrInstanceOf(TaxLine::class)], 'collect_tax' => 'boolean'];
    }

    public function validateAttributes(array $attributes): void
    {
        $validator = Validator::make($attributes, $this->rules());
        if ($validator->fails()) {
            throw new \InvalidArgumentException($validator->errors()->first());
        }
    }

    public function useDefaultTax(): static
    {
        if (! empty($this->tax_lines)) {
            return $this;
        }
        if (! empty($this->billing_address)) {
            $this->tax_lines = $this->getBillingAddressTax($this->billing_address);
        } else {
            $this->tax_lines = $this->getDefaultTax();
        }

        return $this;
    }

    public function hasDiscount(): bool
    {
        return $this->discount !== null;
    }

    protected function setDiscount($value)
    {
        if ($value instanceof DiscountLine) {
            return $value;
        }
        if (! is_array($value) || empty($value)) {
            return null;
        }

        return new DiscountLine($value);
    }

    protected function discount(): Attribute
    {
        return Attribute::make(get: fn ($value) => $this->setDiscount($value), set: fn ($value) => $this->setDiscount($value));
    }

    protected function setLineItems($value)
    {
        return collect($value ?: [])->map(function ($item) {
            if ($item instanceof LineItem) {
                return $item;
            }
            if (is_array($item) && ! isset($item['taxable'])) {
                $item['taxable'] = true;
            }

            return new LineItem($item);
        });
    }

    protected function lineItems(): Attribute
    {
        return Attribute::make(get: fn ($value) => $this->setLineItems($value), set: fn ($value) => $this->setLineItems($value));
    }

    protected function setTaxLines($value)
    {
        $taxes = collect($value ?: [])->map(function ($item) {
            if ($item instanceof TaxLine) {
                return $item;
            }
            if (! is_array($item)) {
                return null;
            }

            return TaxLine::firstOrNew(['id' => $item['id'] ?? null], $item);
        })->filter();

        return $this->taxes = $taxes;
    }

    protected function taxLines(): Attribute
    {
        return Attribute::make(get: function ($value) {
            return $this->taxes->map(function ($item) {
                return $item->fill(['amount' => $this->getTaxTotal($item)]);
            });
        }, set: fn ($value) => $this->setTaxLines($value));
    }

    protected function totalLineItems(): Attribute
    {
        return Attribute::make(get: function () {
            return $this->line_items->sum('quantity');
        });
    }

    protected function lineItemsQuantity(): Attribute
    {
        return Attribute::make(get: function () {
            return $this->line_items->sum('quantity');
        });
    }

    protected function taxableLineItems(): Attribute
    {
        return Attribute::make(get: fn () => $this->line_items->where('taxable', true)->sum('quantity'));
    }

    protected function subTotal(): Attribute
    {
        return Attribute::make(get: function () {
            if (is_null($this->line_items)) {
                return 0;
            }

            return round($this->line_items->sum('total'), 2);
        });
    }

    protected function taxableSubTotal(): Attribute
    {
        return Attribute::make(get: function () {
            if (is_null($this->line_items)) {
                return 0;
            }

            return $this->line_items->where('taxable', true)->sum('total');
        });
    }

    protected function discountTotal(): Attribute
    {
        return Attribute::make(get: function () {
            $discount = $this->discount;
            if ($discount && $discount instanceof DiscountLine) {
                if ($discount->isFixedAmount()) {
                    return round($discount->value, 2);
                } else {
                    return round($this->sub_total * $discount->value / 100, 2);
                }
            }

            return 0;
        });
    }

    protected function discountPerItem(): Attribute
    {
        return Attribute::make(get: function () {
            if (! $this->total_line_items) {
                return 0;
            }

            return $this->discount_total / $this->total_line_items;
        });
    }

    protected function taxableDiscount(): Attribute
    {
        return Attribute::make(get: function () {
            if ($this->hasDiscount()) {
                return $this->discount_per_item * $this->taxable_line_items;
            }

            return 0;
        });
    }

    protected function hasCompoundTax(): Attribute
    {
        return Attribute::make(get: fn () => $this->taxes->where('type', 'compounded')->count() > 0);
    }

    protected function taxTotal(): Attribute
    {
        return Attribute::make(get: function () {
            $taxLinesWithAmounts = collect($this->attributes['tax_lines'] ?? [])->filter(function ($tax) {
                return isset($tax['amount']) && $tax['amount'] > 0;
            });
            if ($taxLinesWithAmounts->isNotEmpty()) {
                return round($taxLinesWithAmounts->sum('amount'), 2);
            }
            if (! $this->collect_tax) {
                return 0;
            }

            return round($this->tax_lines->sum('amount'), 2);
        });
    }

    protected function totalTaxable(): Attribute
    {
        return Attribute::make(get: fn () => round($this->taxable_sub_total - $this->taxable_discount, 2));
    }

    protected function defaultTaxTotal(): Attribute
    {
        return Attribute::make(get: function () {
            return $this->taxes->whereNotIn('type', ['compounded'])->map(function ($tax) {
                return round($this->total_taxable * $tax->rate / 100, 2);
            })->sum();
        });
    }

    private function getTaxTotal($tax)
    {
        if ($this->has_compound_tax && $this->default_tax_total && $tax->type == 'compounded') {
            return round(($this->total_taxable + $this->default_tax_total) * $tax->rate / 100, 2);
        } else {
            return round($this->total_taxable * $tax->rate / 100, 2);
        }
    }

    protected function shippingTotal(): Attribute
    {
        return Attribute::make(get: fn () => 0);
    }

    protected function grandTotal(): Attribute
    {
        return Attribute::make(get: function () {
            return round($this->sub_total + $this->tax_total + $this->shipping_total - $this->discount_total ?? 0, 2);
        });
    }
}
