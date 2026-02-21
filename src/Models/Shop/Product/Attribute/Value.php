<?php

namespace Coderstm\Models\Shop\Product\Attribute;

use Coderstm\Models\Shop\Product\Attribute;
use Coderstm\Traits\Fileable;
use Coderstm\Traits\Logable;
use Coderstm\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Value extends Model
{
    use Fileable, HasFactory, Logable, SerializeDate;

    protected $table = 'attribute_values';

    protected $casts = ['has_thumbnail' => 'boolean'];

    protected $fillable = ['name', 'has_thumbnail', 'color', 'attribute_id'];

    protected $with = [];

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }
}
