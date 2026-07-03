<?php

namespace Coderstm\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

trait Core
{
    use HasFactory, Logable, SerializeDate, SoftDeletes;

    /**
     * Reload a fresh model instance from the database.
     *
     * @param  array|string  $with
     * @return static|null
     */
    public function fresh($with = [])
    {
        if (! $this->exists) {
            return;
        }

        return $this->find($this->id)->load(is_string($with) ? func_get_args() : $with);
    }

    /**
     * Scope a query to only include onlyActive
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeOnlyActive($query)
    {
        return $query->where('is_active', 1);
    }
}
