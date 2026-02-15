<?php

namespace Coderstm\Models\Shop\Product\Collection;

use Coderstm\Traits\Core;
use Illuminate\Database\Eloquent\Model;

class Condition extends Model
{
    use Core;

    protected $table = 'collection_conditions';

    protected $fillable = ['type', 'relation', 'value'];
}
