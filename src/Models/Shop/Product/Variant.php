<?php

namespace Coderstm\Models\Shop\Product;

use Coderstm\Database\Factories\Shop\Product\VariantFactory;
use Coderstm\Models\Shop\LineItem;
use Coderstm\Models\Shop\Product;
use Coderstm\Models\Shop\Product\Variant\Option;
use Coderstm\Models\Subscription\Plan;
use Coderstm\Services\Resource;
use Coderstm\Traits\Core;
use Coderstm\Traits\Fileable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Variant extends Model
{
    use Core, Fileable;

    protected $fillable = ['price', 'compare_at_price', 'cost_per_item', 'taxable', 'recurring', 'track_inventory', 'out_of_stock_track_inventory', 'sku', 'origin', 'harmonized_system_code', 'barcode', 'product_id', 'is_default'];

    protected $appends = ['inventories_available', 'title', 'price_formatted', 'in_stock'];

    protected $with = ['options', 'weight', 'thumbnail', 'inventories.location:id,name'];

    protected $casts = ['taxable' => 'boolean', 'recurring' => 'boolean', 'track_inventory' => 'boolean', 'out_of_stock_track_inventory' => 'boolean', 'is_default' => 'boolean'];

    public function product()
    {
        return $this->belongsTo(Product::class)->withOnly(['thumbnail']);
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    public function active_inventories()
    {
        return $this->hasMany(Inventory::class)->active();
    }

    public function line_items()
    {
        return $this->hasMany(LineItem::class);
    }

    public function weight()
    {
        return $this->morphOne(Weight::class, 'weightable');
    }

    public function options()
    {
        return $this->hasMany(Option::class);
    }

    public function recurringPlans()
    {
        return $this->hasMany(Plan::class, 'variant_id');
    }

    public function getOptions()
    {
        return $this->options->map(function ($option) {
            return ['id' => $option->id, 'name' => $option->name, 'value' => $option->value];
        })->toArray();
    }

    public function getOptionsWithValues()
    {
        return $this->options->map(function ($option) {
            return ['id' => $option->id, 'name' => $option->name, 'value' => $option->value, 'values' => $option->values, 'type' => $option->attribute->type];
        })->toArray();
    }

    public function getInventoriesAvailableAttribute()
    {
        $location = $this->active_inventories_count;
        $available = $this->active_inventories_sum_available;
        if (! $this->track_inventory) {
            return $location > 1 ? "Stocked at {$location} locations" : "Stocked at {$location} location";
        }

        return $location > 1 ? "{$available} available at {$location} locations" : "{$available} available at {$location} location";
    }

    public function getInStockAttribute()
    {
        if ($this->track_inventory && $this->out_of_stock_track_inventory) {
            return $this->active_inventories_sum_available > 0;
        }

        return true;
    }

    public function getTitleAttribute()
    {
        if ($this->is_default) {
            return 'Default';
        }

        return $this->options->map(function ($option) {
            return $option->value;
        })->join(' / ');
    }

    public function getPriceFormattedAttribute()
    {
        return format_amount($this->price);
    }

    public function scopeOnlyTrackInventory($query)
    {
        return $query->where('track_inventory', 1);
    }

    public function scopeStockIn($query)
    {
        return $query->where(function ($query) {
            $query->where('track_inventory', 1)->where('out_of_stock_track_inventory', 1);
        })->orWhere(function ($query) {
            $query->where('track_inventory', 1)->where('out_of_stock_track_inventory', 0)->having('active_inventories_sum_available', '>', 0);
        })->orWhere('track_inventory', 0);
    }

    public function saveRelated(Resource $resource)
    {
        if ($resource->filled('thumbnail.id')) {
            $this->product->media()->syncWithoutDetaching($resource->input('thumbnail.id'));
            $this->media()->sync($resource->input('thumbnail.id'));
        } else {
            $this->media()->detach();
        }
        if ($resource->filled('inventories')) {
            foreach (collect($resource['inventories']) as $inventory) {
                $inventory['location_id'] = $inventory['location']['id'];
                $this->inventories()->updateOrCreate(['location_id' => $inventory['location_id']], $inventory);
            }
        }
        if ($resource->filled('options')) {
            foreach (collect($resource['options']) as $item) {
                if ($item['id']) {
                    $option = Option::find($item['id']);
                } else {
                    $productOption = $this->product->options()->firstOrCreate(['name' => $item['name']], $item);
                    $option = $this->options()->updateOrCreate(['option_id' => $productOption->id], $item);
                }
                if ($option->value != $item['value']) {
                    $option->update(['value' => $item['value']]);
                    if (isset($option['values'])) {
                        $values = collect($option['values'])->pluck('name')->toArray();
                        if (! in_array($item['value'], $values)) {
                            $option->attribute->updateValue($item['value']);
                        }
                    }
                }
            }
        }
        if ($resource->filled('recurring_plans')) {
            $planIds = collect($resource['recurring_plans'])->pluck('id')->filter();
            $this->recurringPlans()->whereNotIn('id', $planIds)->delete();
            foreach ($resource['recurring_plans'] as $planData) {
                $this->recurringPlans()->updateOrCreate(['id' => $planData['id'] ?? null], $planData);
            }
        }

        return $this;
    }

    protected static function newFactory()
    {
        return VariantFactory::new();
    }

    protected static function booted()
    {
        parent::booted();
        static::deleted(function ($model) {
            $model->inventories()->each(function ($item) {
                $item->delete();
            });
            $model->options()->each(function ($item) {
                $item->delete();
            });
        });
        static::deleting(function ($model) {
            $model->line_items()->update(['is_variant_deleted' => true]);
        });
        static::restoring(function ($model) {
            $model->line_items()->withTrashed()->update(['is_variant_deleted' => false]);
        });
        static::addGlobalScope('count', function (Builder $builder) {
            $builder->withCount(['active_inventories'])->withSum('active_inventories', 'available');
        });
    }

    public function getShortCodes(): array
    {
        return ['id' => $this->id, 'product_id' => $this->product_id, 'title' => $this->title, 'sku' => $this->sku, 'barcode' => $this->barcode, 'product_name' => $this->product?->title, 'product_url' => app_url("/products/{$this->product_id}"), 'admin_url' => admin_url("/products/{$this->product_id}"), 'price' => format_amount($this->price ?? 0), 'compare_at_price' => format_amount($this->compare_at_price ?? 0), 'cost_per_item' => format_amount($this->cost_per_item ?? 0), 'raw_price' => $this->price ?? 0, 'track_inventory' => (bool) $this->track_inventory, 'available_quantity' => $this->inventories_available ?? 0, 'in_stock' => (bool) $this->in_stock, 'total_inventory' => $this->inventories->sum('available'), 'options' => $this->options->map(fn ($option) => ['name' => $option->name, 'value' => $option->value])->toArray(), 'is_recurring' => (bool) $this->recurring, 'is_taxable' => (bool) $this->taxable, 'is_default' => (bool) $this->is_default, 'origin' => $this->origin, 'harmonized_system_code' => $this->harmonized_system_code];
    }
}
