<?php

namespace Coderstm\Models\Shop\Product\Attribute;

use Coderstm\Traits\Logable;
use Coderstm\Traits\Fileable;
use Coderstm\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Coderstm\Models\Shop\Product\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Value extends Model
{
    use HasFactory, Logable, SerializeDate, Fileable;

    protected $table = 'attribute_values';

    protected $casts = [
        'has_thumbnail' => 'boolean',
    ];

    protected $fillable = [
        'name',
        'has_thumbnail',
        'color',
        'thumbnail_id',
        'attribute_id',
    ];

    protected $with = [
        // 'media',
        // 'attribute',
    ];

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }
}
