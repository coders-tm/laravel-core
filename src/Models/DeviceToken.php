<?php

namespace Coderstm\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DeviceToken extends Model
{
    use HasFactory;

    protected $fillable = ['token', 'app_id'];

    public function tokenable(): MorphTo
    {
        return $this->morphTo();
    }
}
