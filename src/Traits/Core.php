<?php

namespace Coderstm\Traits;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

trait Core
{
    use HasFactory, Logable, SerializeDate, SoftDeletes;

    public function fresh($with = [])
    {
        if (! $this->exists) {
            return;
        }

        return $this->find($this->id)->load(is_string($with) ? func_get_args() : $with);
    }

    public function scopeOnlyActive($query)
    {
        return $query->where('is_active', 1);
    }
}
