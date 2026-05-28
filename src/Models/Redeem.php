<?php

namespace Coderstm\Models;

use Coderstm\Coderstm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Redeem extends Model
{
    protected $fillable = ['redeemable_type', 'redeemable_id', 'coupon_id', 'user_id', 'amount'];

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coderstm::$couponModel);
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
