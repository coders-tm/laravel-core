<?php

namespace Coderstm\Models;

use Coderstm\Models\Coupon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Redeem extends Model
{
    protected $fillable = [
        'coupon_id',
        'user_id',
        'amount',
    ];

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function redeemable()
    {
        return $this->morphTo();
    }
}
