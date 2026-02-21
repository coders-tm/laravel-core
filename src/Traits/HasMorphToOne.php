<?php

namespace Coderstm\Traits;

use Coderstm\Relations\MorphToOne;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasMorphToOne
{
    public function morphToOne($related, $name, $table = null, $foreignPivotKey = null, $relatedPivotKey = null, $parentKey = null, $relatedKey = null, $inverse = false)
    {
        $caller = $this->guessBelongsToManyRelation();
        $instance = $this->newRelatedInstance($related);
        $foreignPivotKey = $foreignPivotKey ?: $name.'_id';
        $relatedPivotKey = $relatedPivotKey ?: $instance->getForeignKey();
        $table = $table ?: Str::plural($name);

        return $this->newMorphToOne($instance->newQuery(), $this, $name, $table, $foreignPivotKey, $relatedPivotKey, $parentKey ?: $this->getKeyName(), $relatedKey ?: $instance->getKeyName(), $caller, $inverse);
    }

    protected function newMorphToOne(Builder $query, Model $parent, $name, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName = null, $inverse = false)
    {
        return new MorphToOne($query, $parent, $name, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName, $inverse);
    }

    public function morphedByOne($related, $name, $table = null, $foreignPivotKey = null, $relatedPivotKey = null, $parentKey = null, $relatedKey = null)
    {
        $foreignPivotKey = $foreignPivotKey ?: $this->getForeignKey();
        $relatedPivotKey = $relatedPivotKey ?: $name.'_id';

        return $this->morphToOne($related, $name, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, true);
    }
}
