<?php

namespace Coderstm\Models;

use Illuminate\Database\Eloquent\Model;

class Action extends Model
{
    public $timestamps = false;

    protected $fillable = ['actionable_type', 'actionable_id', 'name'];

    public function actionable()
    {
        return $this->morphTo();
    }
}
