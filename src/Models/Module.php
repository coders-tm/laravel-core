<?php

namespace Coderstm\Models;

use Coderstm\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use SerializeDate;

    protected $fillable = [
        'name',
        'icon',
        'url',
        'show_menu',
        'sort_order',
    ];

    protected $casts = [
        'show_menu' => 'boolean',
    ];

    protected $appends = ['permissions'];

    protected function permissions(): Attribute
    {
        return Attribute::make(
            get: fn () => Permission::forModule($this),
        );
    }
}
