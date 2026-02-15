<?php

namespace Coderstm\Repository;

use Coderstm\Models\Shop\Order;
use Coderstm\Models\Shop\Order\DiscountLine;
use Coderstm\Models\Shop\Order\LineItem;
use Coderstm\Models\Shop\Order\TaxLine;
use Illuminate\Support\Collection;

class InvoiceRepository extends Order
{
    public $timestamps = false;

    protected $fillable = ['customer_id', 'due_date', 'billing_address', 'note', 'collect_tax', 'sub_total', 'tax_total', 'discount_total', 'line_items', 'grand_total', 'paid_total', 'refund_total', 'source', 'discount', 'orderable_id', 'orderable_type'];

    protected $with = [];

    protected $appends = ['sub_total', 'tax_total', 'total_line_items', 'discount_total', 'grand_total'];

    protected $casts = [];

    protected $hidden = [];

    private Collection $taxes;

    public function __construct(array $attributes = [])
    {
        if (isset($attributes['line_items'])) {
            $attributes['line_items'] = collect($attributes['line_items']);
        }
        if (! isset($attributes['tax_lines']) || empty($attributes['tax_lines'])) {
            $attributes['tax_lines'] = default_tax();
        }
        if (isset($attributes['billing_address']) && ! empty($attributes['billing_address'])) {
            $attributes['tax_lines'] = billing_address_tax($attributes['billing_address']);
        }
        $this->taxes = collect(has($attributes)->tax_lines ?: [])->map(function ($item) {
            return Taxline::firstOrNew(['id' => has($item)->id], $item)->fill($item);
        });
        parent::__construct($attributes);
    }

    public function hasDiscount(): bool
    {
        return ! is_null($this->discount) ?: false;
    }

    public function getLineItemsAttribute($value)
    {
        return collect($value ?: [])->map(function ($item) {
            $lineItem = new LineItem($item);
            if (isset($item['discount'])) {
                $lineItem->setRelation('discount', new DiscountLine($item['discount']));
            }

            return $lineItem;
        });
    }

    public function getDiscountAttribute($value)
    {
        return new DiscountLine($value ?: []);
    }

    public function getTotalLineItemsAttribute()
    {
        return $this->line_items->count();
    }

    public function getTaxableLineItemsAttribute()
    {
        return $this->line_items->count();
    }

    public function getSubTotalAttribute()
    {
        if (is_null($this->line_items)) {
            return 0;
        }

        return round($this->line_items->sum('total'), 2);
    }

    public function getTaxableSubTotalAttribute()
    {
        if (is_null($this->line_items)) {
            return 0;
        }

        return $this->line_items->sum('total');
    }

    public function getDiscountTotalAttribute()
    {
        if ($this->discount) {
            if ($this->discount->isFixedAmount()) {
                return round($this->discount->value, 2);
            } else {
                return round($this->sub_total * $this->discount->value / 100, 2);
            }
        }

        return 0;
    }

    public function getDiscountPerItemAttribute()
    {
        if (! $this->total_line_items) {
            return 0;
        }

        return $this->discount_total / $this->total_line_items;
    }

    public function getTaxableDiscountAttribute()
    {
        if ($this->hasDiscount()) {
            return $this->discount_per_item * $this->taxable_line_items;
        }

        return 0;
    }

    public function getHasCompoundTaxAttribute()
    {
        return $this->taxes->where('type', 'compounded')->count() > 0;
    }

    public function getTaxTotalAttribute()
    {
        if (! $this->collect_tax) {
            return 0;
        }

        return round($this->tax_lines->sum('amount'), 2);
    }

    public function getTotalTaxableAttribute()
    {
        return round($this->taxable_sub_total - $this->taxable_discount, 2);
    }

    public function getDefaultTaxTotalAttribute()
    {
        return $this->taxes->whereNotIn('type', ['compounded'])->map(function ($tax) {
            return round($this->total_taxable * $tax->rate / 100, 2);
        })->sum();
    }

    public function getTaxLinesAttribute($value)
    {
        return $this->taxes->map(function ($item) {
            return $item->fill(['amount' => $this->getTaxTotal($item)]);
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

    public function getGrandTotalAttribute()
    {
        return round($this->sub_total + $this->tax_total - $this->discount_total ?? 0, 2);
    }
}
