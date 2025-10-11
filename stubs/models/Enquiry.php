<?php

namespace App\Models;

use Coderstm\Models\Enquiry as Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enquiry extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'status',
        'seen',
        'is_archived',
        'user_archived',
        'order_id',
        'source',
    ];
}
