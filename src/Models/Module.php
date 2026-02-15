<?php

namespace Coderstm\Models;

use Coderstm\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use SerializeDate;

    protected $fillable = ['name', 'icon', 'url', 'show_menu', 'sort_order'];

    protected $casts = ['show_menu' => 'boolean'];

    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }
}
