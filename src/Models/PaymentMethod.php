<?php

namespace Coderstm\Models;

use Coderstm\Traits\Core;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class PaymentMethod extends Model
{
    use Core;

    const STRIPE = 'stripe';
    const RAZORPAY = 'razorpay';
    const PAYPAL = 'paypal';
    const MANUAL = 'manual';

    protected $fillable = [
        'name',
        'label',
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

    protected function label(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ?? $this->name,
        );
    }


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

    public static function razorpay()
    {
        return static::findProvider(static::RAZORPAY);
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
                'label',
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

    public static function __callStatic($method, $parameters)
    {
        if (preg_match('/(.+)Id$/', $method, $matches)) {
            $providerMethod = $matches[1];
            if (method_exists(static::class, $providerMethod)) {
                return static::$providerMethod()?->id;
            }
        }
        return parent::__callStatic($method, $parameters);
    }
}
