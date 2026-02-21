<?php

namespace Coderstm\Models\Shop;

use Coderstm\Database\Factories\Shop\ProductFactory;
use Coderstm\Models\Shop\Product\Attribute\Value;
use Coderstm\Models\Shop\Product\Category;
use Coderstm\Models\Shop\Product\Collection;
use Coderstm\Models\Shop\Product\Inventory;
use Coderstm\Models\Shop\Product\Option;
use Coderstm\Models\Shop\Product\Tag;
use Coderstm\Models\Shop\Product\Variant;
use Coderstm\Models\Shop\Product\Vendor;
use Coderstm\Services\Resource;
use Coderstm\Traits\Core;
use Coderstm\Traits\Fileable;
use Coderstm\Traits\Helpers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Product extends Model
{
    use Core, Fileable, HasSlug, Helpers;

    protected $fillable = ['title', 'slug', 'identifier', 'description', 'status', 'meta_title', 'meta_keywords', 'meta_description', 'has_variant', 'is_gift_card', 'category', 'vendor'];

    protected $filterable = ['title', 'slug', 'description', 'active', 'status', 'has_variant'];

    protected $with = ['thumbnail'];

    protected $appends = ['price', 'available', 'inventory_html'];

    protected $casts = ['has_variant' => 'boolean'];

    protected $hidden = ['vendor_id', 'category_id'];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('title')->saveSlugsTo('slug')->preventOverwrite();
    }

    public function collections()
    {
        return $this->morphToMany(Collection::class, 'collectionable');
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function options()
    {
        return $this->hasMany(Option::class);
    }

    public function default_variant()
    {
        return $this->hasOne(Variant::class)->where('is_default', 1);
    }

    public function variants()
    {
        return $this->hasMany(Variant::class)->where('is_default', 0);
    }

    public function line_items()
    {
        return $this->hasMany(LineItem::class);
    }

    public function inventories()
    {
        return $this->hasManyThrough(Inventory::class, Variant::class);
    }

    protected function price(): Attribute
    {
        return Attribute::make(get: function () {
            if ($this->has_variant) {
                $min = format_amount($this->variants_min_price ?? 0);
                $max = format_amount($this->variants_max_price ?? 0);

                return $min != $max ? "{$min} - {$max}" : $min;
            }

            return format_amount($this->default_variant_min_price ?? 0);
        });
    }

    protected function inventoryHtml(): Attribute
    {
        return Attribute::make(get: function () {
            $inventories = $this->total_inventories_available;
            $variants = $this->variants_count;
            $noInventory = $inventories <= 0 ? 'no-inventory' : '';
            if ($this->trackable_variants_count) {
                if ($this->has_variant) {
                    return "<span class=\"{$noInventory}\">{$inventories}</span> in stock for {$variants} variants";
                } else {
                    return "<span class=\"{$noInventory}\">{$inventories}</span> in stock";
                }
            } elseif ($this->default_variant && $this->default_variant->track_inventory) {
                return "<span class=\"{$noInventory}\">{$inventories}</span> in stock";
            }

            return 'Inventory not tracked';
        });
    }

    protected function available(): Attribute
    {
        return Attribute::make(get: fn () => "{$this->total_inventories_available} available");
    }

    public function setVendorAttribute($vendor)
    {
        if (isset($vendor['id']) && Vendor::find($vendor['id'])) {
            $this->attributes['vendor_id'] = $vendor['id'];
        } elseif (isset($vendor['name'])) {
            $vendor = Vendor::firstOrCreate(['name' => $vendor['name']]);
            $this->attributes['vendor_id'] = $vendor->id;
        } else {
            $this->attributes['vendor_id'] = null;
        }
    }

    public function setCategoryAttribute($category)
    {
        if (isset($category['id']) && Category::find($category['id'])) {
            $this->attributes['category_id'] = $category['id'];
        } elseif (isset($category['name'])) {
            $category = Category::firstOrCreate(['name' => $category['name']]);
            $this->attributes['category_id'] = $category->id;
        } else {
            $this->attributes['category_id'] = null;
        }
    }

    public function setTags(array $items)
    {
        $tags = [];
        foreach ($items as $item) {
            if (isset($item['id']) && Tag::find($item['id'])) {
                $tags[] = $item['id'];
            } elseif (isset($item['name'])) {
                $item = Tag::firstOrCreate(['name' => $item['name']]);
                $tags[] = $item->id;
            }
        }
        $this->tags()->sync($tags);
    }

    public function setCollections(array $items)
    {
        $collections = [];
        foreach ($items as $item) {
            if (isset($item['id']) && Collection::find($item['id'])) {
                $collections[] = $item['id'];
            } elseif (isset($item['name'])) {
                $item = Collection::firstOrCreate(['name' => $item['name']]);
                $collections[] = $item->id;
            }
        }
        $this->collections()->sync($collections);
    }

    public function setOptions(array $options)
    {
        $options = collect($options);
        $this->options()->whereNotIn('id', $options->pluck('id')->filter())->each(function ($option) {
            $option->variant_options()->each(function ($item) {
                $item->delete();
            });
            $option->delete();
        });
        foreach ($options as $option) {
            $option = array_filter($option);
            $newValues = collect($option['values'] ?? []);
            $singleOption = $this->options()->where('name', $option['name'])->first();
            if (isset($option['values']) && isset($option['is_custom']) && $option['is_custom']) {
                $option['custom_values'] = $newValues;
            }
            $singleOption = $this->options()->updateOrCreate(['name' => $option['name']], $option);
            if (isset($option['attribute_id'], $option['values']) && $option['attribute_id'] && $singleOption) {
                $singleOption->attribue_values()->sync([]);
                foreach ($newValues->pluck('name') as $value) {
                    $singleOption->attribue_values()->attach(Value::firstOrCreate(['name' => $value, 'attribute_id' => $option['attribute_id']]));
                }
            }
        }
    }

    public function setDefaultVariant(array $variant)
    {
        if ($variant) {
            $variant['is_default'] = true;
            $this->default_variant = $this->default_variant()->updateOrCreate(['id' => isset($variant['id']) ? $variant['id'] : 'new'], $variant);
            $this->default_variant->saveRelated(new Resource($variant));
        }

        return $this;
    }

    public function setVariants(array $variants)
    {
        $this->variants()->whereNotIn('id', collect($variants)->pluck('id')->filter(function ($value) {
            return is_int($value);
        }))->each(function ($item) {
            $item->delete();
        });
        foreach (collect($variants) as $item) {
            $item['is_default'] = false;
            $variant = $this->variants()->updateOrCreate(['id' => isset($item['id']) ? $item['id'] : 'new'], $item);
            $variant->saveRelated(new Resource($item));
        }
    }

    public function setDescriptionAttribute($value)
    {
        $this->attributes['description'] = $value ?? '';
    }

    public function scopeFilters($query, array $conditions = [], $match = 'any')
    {
        if (count($conditions)) {
            $where = $match == 'any' ? 'orWhere' : 'where';
            $whereHas = $match == 'any' ? 'orWhereHas' : 'whereHas';
            $conditions = collect($conditions)->map(function ($item) {
                if (is_string($item)) {
                    return json_decode($item, true);
                }

                return $item;
            })->toArray();
            $query->orWhere(function ($query) use ($conditions, $where, $whereHas) {
                $filters = $this->getFilters($conditions);
                foreach ($filters as $field => $filter) {
                    if (in_array($field, $this->filterable)) {
                        $query->{$where}([$filter]);
                    } elseif ($field == 'inventories') {
                        $query->{$whereHas}('inventories', function ($q) use ($filter) {
                            $q->select(DB::raw('SUM(available) as inventories'))->havingRaw(implode(' ', $filter));
                        });
                    } elseif ($field == 'variants') {
                        $query->{$whereHas}('variants', function ($q) use ($filter, $where) {
                            $this->queryWhere($q, $filter, $where);
                        });
                        $query->{$whereHas}('default_variant', function ($q) use ($filter, $where) {
                            $this->queryWhere($q, $filter, $where);
                        });
                    } elseif ($this->has($field)) {
                        $query->{$whereHas}($field, function ($q) use ($filter, $where) {
                            $this->queryWhere($q, $filter, $where);
                        });
                    }
                }
            });
        }

        return $query;
    }

    protected function queryWhere(Builder $query, $filters, $where)
    {
        if (count($filters)) {
            foreach ($filters as $key => $filter) {
                if ($key == 0) {
                    $query->where([$filter]);
                } else {
                    $query->{$where}([$filter]);
                }
            }
        }
    }

    public function scopeSortBy($query, $column = 'created_at', $direction = 'asc')
    {
        switch ($column) {
            case 'BEST_SELLING':
                break;
            case 'TITLE_ASC':
                $query->orderBy('title', 'asc');
                break;
            case 'TITLE_DESC':
                $query->orderBy('title', 'desc');
                break;
            case 'PRICE_DESC':
                $query->select('products.*')->leftJoin('variants', 'products.id', '=', 'variants.product_id')->addSelect(DB::raw('MAX(variants.price) AS variants_price'))->groupBy('products.id')->orderBy('variants_price', 'desc');
                break;
            case 'PRICE_ASC':
                $query->select('products.*')->leftJoin('variants', 'products.id', '=', 'variants.product_id')->addSelect(DB::raw('MAX(variants.price) AS variants_price'))->groupBy('products.id')->orderBy('variants_price', 'asc');
                break;
            case 'price':
                $query->select('products.*')->leftJoin('variants', 'products.id', '=', 'variants.product_id')->addSelect(DB::raw('MAX(variants.price) AS variants_price'))->groupBy('products.id')->orderBy('variants_price', $direction);
                break;
            case 'CREATED_AT_DESC':
                $query->orderBy('created_at', 'desc');
                break;
            case 'CREATED_AT':
                $query->orderBy('created_at', 'asc');
                break;
            default:
                $query->orderBy($column ?? 'created_at', $direction ?? 'desc');
                break;
        }

        return $query;
    }

    public function scopeOnlyActive($query)
    {
        return $query->where('status', 'Active');
    }

    protected static function newFactory()
    {
        return ProductFactory::new();
    }

    public static function generateIdentifier()
    {
        $identifier = fake()->regexify('[a-z]{3}[0-9]{2}[a-z]{2}[0-9]{1}');
        while (self::where('identifier', $identifier)->exists()) {
            $identifier = fake()->regexify('[a-z]{3}[0-9]{2}[a-z]{2}[0-9]{1}');
        }

        return $identifier;
    }

    protected static function booted()
    {
        parent::booted();
        static::creating(function ($model) {
            $model->identifier = $model->identifier ?? self::generateIdentifier();
        });
        static::deleting(function ($model) {
            $model->line_items()->update(['is_product_deleted' => true]);
        });
        static::restoring(function ($model) {
            $model->line_items()->withTrashed()->update(['is_product_deleted' => false]);
        });
        static::addGlobalScope('count', function (Builder $builder) {
            $builder->withMax('variants', 'price')->withMin('variants', 'price')->withMin('default_variant', 'price')->withCount(['variants', 'variants as trackable_variants_count' => function (Builder $query) {
                $query->where('track_inventory', 1);
            }, 'inventories as total_inventories_available' => function (Builder $query) {
                $query->select(DB::raw('SUM(available) as available_sum'))->active()->whereHas('variant', function ($query) {
                    $query->where('track_inventory', 1);
                });
            }]);
        });
    }
}
