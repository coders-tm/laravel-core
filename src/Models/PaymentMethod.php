<?php

namespace Coderstm\Models;

use Coderstm\Traits\Core;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use Core;

    const STRIPE = 'stripe';
    const RAZORPAY = 'razorpay';
    const PAYPAL = 'paypal';
    const MANUAL = 'manual';

    protected $fillable = [
        'name',
        'provider',
        'link',
        'logo',
        'description',
        'credentials',
        'methods',
        'active',
        'capture',
        'additional_details',
        'payment_instructions',
        'test_mode',
        'transaction_fee',
        'webhook',
    ];

    protected $casts = [
        'active' => 'boolean',
        'test_mode' => 'boolean',
        'credentials' => 'array',
        'methods' => 'array',
    ];

    public function getConfigsAttribute()
    {
        return collect($this->credentials)->mapWithKeys(function ($credential) {
            return [$credential['key'] => $credential['value']];
        })->all();
    }

    public function scopeEnabled($query)
    {
        return $query->where('active', 1);
    }

    public function scopeDisabled($query)
    {
        return $query->where('active', 0);
    }

    public function scopeManual($query)
    {
        return $query->where('provider', 'manual');
    }

    public static function findProvider($provider)
    {
        return static::firstWhere('provider', $provider);
    }

    public static function stripe()
    {
        return static::findProvider(static::STRIPE);
    }
}
