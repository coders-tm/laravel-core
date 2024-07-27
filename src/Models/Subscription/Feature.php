<?php

namespace Coderstm\Models\Subscription;

use Coderstm\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Feature extends Model
{
    use HasFactory, SerializeDate;

    protected $table = 'features';

    protected $fillable = [
        'label',
        'slug',
        'type',
        'resetable',
        'description',
    ];

    protected $casts = [
        'resetable' => 'boolean',
    ];

    public function isBoolean(): bool
    {
        return $this->type === 'boolean';
    }

    static function findBySlug($slug): ?self
    {
        return static::where('slug', $slug)->first();
    }
}
