<?php

namespace Coderstm\Models;

use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    protected $fillable = [
        'amount',
        'reason',
        'payment_id',
    ];
}
