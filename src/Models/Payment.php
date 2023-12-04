<?php

namespace Coderstm\Models;

use Coderstm\Traits\Core;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use Core;

    protected $fillable = [
        'payment_method_id',
        'transaction_id',
        'amount',
        'capturable',
        'status',
        'note',
        'options',
    ];

    protected $hidden = [
        'paymentable_type',
        'paymentable_id',
    ];

    protected $casts = [
        'capturable' => 'boolean',
    ];

    protected $with = [
        'paymentMethod',
    ];

    public function paymentable()
    {
        return $this->morphTo();
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
