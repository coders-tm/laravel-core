<?php

namespace Coderstm\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceToken extends Model
{
    use HasFactory;

    protected $fillable = ['token'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
