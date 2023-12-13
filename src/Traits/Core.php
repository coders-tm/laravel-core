<?php

namespace Coderstm\Traits;

use Coderstm\Traits\Logable;
use Coderstm\Traits\SerializeDate;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

trait Core
{
    use SoftDeletes, HasFactory, Logable, SerializeDate;

    /**
     * Reload a fresh model instance from the database.
     *
     * @param  array|string  $with
     * @return static|null
     */
    public function fresh($with = [])
    {
        if (!$this->exists) {
            return;
        }

        return $this->find($this->id)->load(is_string($with) ? func_get_args() : $with);
    }

    /**
     * Scope a query to only include onlyActive
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOnlyActive($query)
    {
        return $query->where('is_active', 1);
    }
}
