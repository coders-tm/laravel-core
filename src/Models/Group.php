<?php

namespace Coderstm\Models;

use Coderstm\Models\Permission;
use Coderstm\Traits\Base;
use Coderstm\Traits\HasPermission;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use Base, HasPermission;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = [
        'permissions',
    ];

    /**
     * Get the parent groupable model.
     */
    public function groupable()
    {
        return $this->morphTo();
    }
}