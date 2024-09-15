<?php

namespace Coderstm\Models\Page;

use Coderstm\Traits\Logable;
use Coderstm\Traits\SerializeDate;
use Coderstm\Traits\JsonCompressible;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Block extends Model
{
    use HasFactory, Logable, SerializeDate, JsonCompressible;

    protected $table = 'page_blocks';

    protected $fillable = [
        'data',
    ];

    /**
     * Interact with the model's JSON data column.
     */
    protected function data(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => $this->uncompress($value),
            set: fn(array $value) => gzcompress(json_encode($value))
        );
    }
}
