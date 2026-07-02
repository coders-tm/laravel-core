<?php

namespace Coderstm\Models;

use Coderstm\Traits\Core;
use Coderstm\Traits\HasPermission;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use Core, HasPermission;

    protected $fillable = ['name', 'description'];

    protected $with = ['permissions'];

    public function groupable()
    {
        return $this->morphTo();
    }
}
