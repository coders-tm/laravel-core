<?php

namespace Coderstm\Models;

use Coderstm\Traits\Core;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use Core;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'company',
        'phone_number',
        'line1',
        'line2',
        'city',
        'state',
        'state_code',
        'postal_code',
        'country',
        'country_code',
        'default',
        'ref',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'default' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'addressable_type',
        'addressable_id',
    ];

    public function addressable()
    {
        return $this->morphTo();
    }

    /**
     * Get the full address of the Address.
     *
     * @return bool
     */
    public function getLabelAttribute()
    {
        return implode(', ', collect($this->attributes)->filter()->only([
            'line1',
            'line2',
            'city',
            'state',
            'country',
            'postal_code',
        ])->toArray());
    }
}
