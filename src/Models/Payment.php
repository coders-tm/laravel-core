<?php

namespace Coderstm\Models;

use Coderstm\Traits\Core;
use Laravel\Cashier\Payment as CashierPayment;
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

    public static function createFromRazorpay(array $options)
    {
        return static::create($options + [
            'payment_method_id' => PaymentMethod::razorpayId(),
        ]);
    }

    public static function createFromPaypal(array $options)
    {
        return static::create($options + [
            'payment_method_id' => PaymentMethod::paypalId(),
        ]);
    }

    public static function createFromStripe(array $options)
    {
        return static::create($options + [
            'payment_method_id' => PaymentMethod::stripeId(),
        ]);
    }

    public static function createFromStripePayment(CashierPayment $payment, array $options = [])
    {
        return static::createUsingStripe($options + [
            'transaction_id' => $payment->id,
            'amount' => $payment->amount / 100,
            'status' => $payment->status,
        ]);
    }
}
