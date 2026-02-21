<?php

namespace Coderstm\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Concerns\SupportsDefaultModels;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class MorphToOne extends MorphToMany
{
    use SupportsDefaultModels;

    public function getResults()
    {
        return $this->first() ?: $this->getDefaultFor($this->getRelated());
    }

    public function initRelation(array $models, $relation)
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->getDefaultFor($model));
        }

        return $models;
    }

    public function match(array $models, Collection $results, $relation)
    {
        $dictionary = $this->buildDictionary($results);
        foreach ($models as $model) {
            if (isset($dictionary[$key = $model->{$this->parentKey}])) {
                $value = $dictionary[$key];
                $model->setRelation($relation, reset($value));
            }
        }

        return $models;
    }

    public function newRelatedInstanceFor(Model $parent)
    {
        return $this->related->newInstance();
    }
}
