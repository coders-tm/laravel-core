<?php

namespace Coderstm\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BelongsToOne extends BelongsToMany
{
    public function initRelation(array $models, $relation)
    {
        foreach ($models as $model) {
            $model->setRelation($relation, null);
        }

        return $models;
    }

    public function match(array $models, Collection $results, $relation)
    {
        $dictionary = $this->buildDictionary($results);
        foreach ($models as $model) {
            if (isset($dictionary[$key = $model->getKey()])) {
                $model->setRelation($relation, reset($dictionary[$key]) ?: null);
            }
        }

        return $models;
    }
}
