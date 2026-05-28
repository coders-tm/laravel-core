<?php

namespace Coderstm\Models;

use Coderstm\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use SerializeDate;

    protected $fillable = ['module_id', 'action', 'description', 'scope'];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function permissionable()
    {
        return $this->morphTo();
    }
}
