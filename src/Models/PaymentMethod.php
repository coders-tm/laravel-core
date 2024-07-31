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
        'credentials' => 'collection',
        'methods' => 'array',
    ];

    public function getConfigsAttribute()
    {
        return $this->credentials->mapWithKeys(function ($item) {
            return [$item['key'] => $item['value']];
        })->all();
    }

    public function payable()
    {
        return $this->provider != static::MANUAL;
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

    public static function paypal()
    {
        return static::findProvider(static::PAYPAL);
    }

    public static function toPublic()
    {
        return static::enabled()->get()->map(function ($item) {
            $credentials = [];
            if ($item->credentials) {
                $credentials = $item->credentials->filter(function ($item) {
                    return isset($item['publish']) && $item['publish'];
                })->mapWithKeys(function ($item) {
                    return [$item['key'] => $item['value']];
                })->all();
            }
            return array_merge($item->only([
                'name',
                'id',
                'provider',
                'logo',
                'payment_instructions',
                'additional_details',
                'methods',
                'transaction_fee'
            ]), $credentials);
        });
    }
}
