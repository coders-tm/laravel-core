<?php

namespace Coderstm\Relations;

use Coderstm\Models\Permissionable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MorphManyPermissions extends MorphMany
{
    public function __construct(Model $parent)
    {
        $instance = new Permissionable;
        if (! $instance->getConnectionName()) {
            $instance->setConnection($parent->getConnectionName());
        }
        parent::__construct($instance->newQuery(), $parent, $instance->getTable().'.permissionable_type', $instance->getTable().'.permissionable_id', $parent->getKeyName());
    }

    public function sync($permissions): void
    {
        $this->parent->syncPermissions(collect($permissions)->map(function ($item, $key) {
            return ['scope' => $key, 'access' => $item['access'] ?? null];
        }));
    }
}
