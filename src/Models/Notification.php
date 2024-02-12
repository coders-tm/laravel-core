<?php

namespace Coderstm\Models;

use Coderstm\Traits\Core;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use Core;

    protected $fillable = [
        'label',
        'subject',
        'type',
        'content',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function markAsDefault()
    {
        $this->update(['is_default' => 1]);

        static::where('type', $this->type)
            ->where('id', '<>', $this->id)
            ->update(['is_default' => 0]);

        return $this;
    }

    public static function default($type = null): static
    {
        return static::where('type', $type)->where('is_default', 1)->firstOrFail();
    }
}
