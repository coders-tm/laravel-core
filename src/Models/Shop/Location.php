<?php

namespace Coderstm\Models\Shop;

use Coderstm\Database\Factories\Shop\LocationFactory;
use Coderstm\Observers\Shop\LocationObserver;
use Coderstm\Traits\Core;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy([LocationObserver::class])]
class Location extends Model
{
    use Core;

    protected $table = 'shop_locations';

    protected $fillable = ['name', 'line1', 'line2', 'city', 'country', 'country_code', 'state', 'state_code', 'postal_code', 'active'];

    protected $casts = ['active' => 'boolean'];

    protected $appends = ['address_label'];

    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    public function getAddressLabelAttribute()
    {
        return implode(', ', collect($this->attributes)->filter()->only(['line1', 'line2', 'city', 'country', 'country_code', 'state', 'state_code', 'postal_code'])->toArray());
    }

    public function scopeOnlyActive($query)
    {
        return $query->where('active', 1);
    }

    protected static function newFactory()
    {
        return LocationFactory::new();
    }
}
