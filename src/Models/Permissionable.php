<?php

namespace Coderstm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Permissionable extends Model
{
    protected $table = 'permissionables';

    public $timestamps = false;

    protected $fillable = ['permissionable_type', 'permissionable_id', 'scope', 'access'];

    protected $casts = ['access' => 'boolean'];

    public function getPivotAttribute()
    {
        return $this;
    }

    public function getIdAttribute()
    {
        return $this->scope;
    }

    public function getModuleIdAttribute()
    {
        $modules = cache()->remember('modules_by_slug', 3600, function () {
            return Module::all()->mapWithKeys(function ($m) {
                return [Str::slug($m->name) => $m->id];
            });
        });
        $parts = explode(':', $this->scope);
        $slug = $parts[0] ?? null;

        return $modules[$slug] ?? null;
    }

    public function getActionAttribute()
    {
        $parts = explode(':', $this->scope);

        return $parts[1] ?? 'read';
    }

    public function permissionable()
    {
        return $this->morphTo();
    }
}
