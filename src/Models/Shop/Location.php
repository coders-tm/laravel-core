<?php

namespace Coderstm\Models\Shop;

use Coderstm\Traits\Core;
use Coderstm\Database\Factories\Shop\LocationFactory;
use Coderstm\Models\Shop\Product\Inventory;
use Coderstm\Models\Shop\Product\Variant;
use Illuminate\Database\Eloquent\Model;
use function Illuminate\Events\queueable;

class Location extends Model
{
    use Core;

    protected $table = 'shop_locations';

    protected $fillable = [
        'name',
        'line1',
        'line2',
        'city',
        'country',
        'country_code',
        'state',
        'state_code',
        'postal_code',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected $appends = [
        'address_label',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    public function getAddressLabelAttribute()
    {
        return implode(', ', collect($this->attributes)->filter()->only([
            'line1',
            'line2',
            'city',
            'country',
            'country_code',
            'state',
            'state_code',
            'postal_code',
        ])->toArray());
    }

    /**
     * Scope a query to only include onlyActive
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOnlyActive($query)
    {
        return $query->where('active', 1);
    }

    protected static function newFactory()
    {
        return LocationFactory::new();
    }

    protected static function booted()
    {
        parent::booted();
        static::created(queueable(function ($model) {
            Variant::chunkById(100, function ($items) use ($model) {
                foreach ($items as $item) {
                    Inventory::updateOrCreate([
                        'active' => true,
                        'variant_id' => $item->id,
                        'location_id' => $model->id,
                    ]);
                }
            });
        }));
        static::updated(queueable(function ($model) {
            if ($model->wasChanged('active')) {
                Variant::chunkById(100, function ($items) use ($model) {
                    foreach ($items as $item) {
                        Inventory::updateOrCreate([
                            'variant_id' => $item->id,
                            'location_id' => $model->id,
                        ], [
                            'active' => $model->active,
                        ]);
                    }
                });
            }
        }));
    }
}
