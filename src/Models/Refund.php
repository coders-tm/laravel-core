<?php

namespace Coderstm\Models;

use Coderstm\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    use SerializeDate;

    protected $fillable = [
        'amount',
        'reason',
        'payment_id',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
