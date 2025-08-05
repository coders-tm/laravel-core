<?php

namespace Coderstm\Models;

use Coderstm\Traits\Core;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class PaymentMethod extends Model
{
    use Core;

    const STRIPE = 'stripe';
    const RAZORPAY = 'razorpay';
    const PAYPAL = 'paypal';
    const GOCARDLESS = 'gocardless';
    const KLARNA = 'klarna';
    const MERCADOPAGO = 'mercadopago';
    const PAYSTACK = 'paystack';
    const XENDIT = 'xendit';
    const FLUTTERWAVE = 'flutterwave';
    const APPLE_PAY = 'apple_pay';
    const GOOGLE_PAY = 'google_pay';
    const DIRECT_DEBIT = 'direct_debit';
    const MANUAL = 'manual';

    const CACHE_KEY = 'payment_methods_configurations';
    public static string $cacheKey = self::CACHE_KEY;

    protected $fillable = [
        'name',
        'label',
        'provider',
        'integration_via',
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
        'options',
        'order'
    ];

    protected $casts = [
        'active' => 'boolean',
        'test_mode' => 'boolean',
        'credentials' => 'collection',
        'methods' => 'array',
        'options' => 'array',
    ];

    protected $appends = ['parent'];

    protected static function cacheKey(): string
    {
        return static::$cacheKey ?? static::CACHE_KEY;
    }

    protected function label(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ?? $this->name,
        );
    }

    protected function parent(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $this->integration_via && config($this->integration_via . '.enabled', false),
        );
    }

    protected function configs(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getConfigs(),
        );
    }

    public function getConfigs()
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

    public static function has($provider)
    {
        return static::enabled()->where('provider', $provider)->exists();
    }

    public static function findProvider($provider)
    {
        return static::enabled()->firstWhere('provider', $provider);
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

    public static function gocardless()
    {
        return static::findProvider(static::GOCARDLESS);
    }

    public static function flutterwave()
    {
        return static::findProvider(static::FLUTTERWAVE);
    }

    public static function toPublic()
    {
        return static::enabled()->orderBy('order')->get()->filter(function ($item) {
            return empty($item->integration_via) || config("{$item->integration_via}.enabled", false);
        })->map(function ($item) {
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
                'integration_via',
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

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        // Update only the affected provider's configuration when saved or deleted
        static::saved(function ($paymentMethod) {
            $provider = $paymentMethod->provider;
            if ($provider !== self::MANUAL) {
                // Update only this specific provider's configuration
                self::updateProviderCache($provider);
                // Apply the updated config
                self::applyProviderConfig($provider);
            }
        });

        static::deleted(function ($paymentMethod) {
            $provider = $paymentMethod->provider;
            if ($provider !== self::MANUAL) {
                // Update only this specific provider's configuration
                self::updateProviderCache($provider);
                // Apply the updated config
                self::applyProviderConfig($provider);
            }
        });
    }

    /**
     * Update the cache for a specific provider
     *
     * @param string $provider
     * @return void
     */
    public static function updateProviderCache(string $provider): void
    {
        // Get all cached configs
        $allConfigs = Cache::get(self::cacheKey(), []);

        // Update or remove the specified provider
        $config = self::getProviderConfig($provider);

        if ($config) {
            // Update the provider's config in the cache
            $allConfigs[$provider] = $config;
        } else {
            // Remove the provider from cache if it no longer exists or is inactive
            unset($allConfigs[$provider]);
        }

        // Store updated configs back to cache
        Cache::forever(self::cacheKey(), $allConfigs);
    }

    /**
     * Get configuration for a specific provider directly from database
     *
     * @param string $provider
     * @return array|null
     */
    public static function getProviderConfig(string $provider): ?array
    {
        $paymentMethod = self::findProvider($provider);
        if (!$paymentMethod) {
            return null;
        }

        switch ($provider) {
            case self::STRIPE:
                return [
                    'stripe.id' => $paymentMethod->id,
                    'cashier.id' => $paymentMethod->id,
                    'stripe.key' => $paymentMethod->configs['API_KEY'],
                    'stripe.secret' => $paymentMethod->configs['API_SECRET'],
                    'cashier.key' => $paymentMethod->configs['API_KEY'],
                    'cashier.secret' => $paymentMethod->configs['API_SECRET'],
                    'cashier.webhook.secret' => $paymentMethod->configs['WEBHOOK_SECRET'],
                    'stripe.enabled' => $paymentMethod->active,
                ];

            case self::PAYPAL:
                $mode = $paymentMethod->test_mode ? 'sandbox' : 'live';
                return [
                    'paypal.id' => $paymentMethod->id,
                    'paypal.mode' => $mode,
                    "paypal.{$mode}.client_id" => $paymentMethod->configs['CLIENT_ID'],
                    "paypal.{$mode}.client_secret" => $paymentMethod->configs['CLIENT_SECRET'],
                    'paypal.notify_url' => $paymentMethod->webhook,
                    'paypal.enabled' => $paymentMethod->active,
                ];

            case self::RAZORPAY:
                return [
                    'razorpay.id' => $paymentMethod->id,
                    'razorpay.key_id' => $paymentMethod->configs['API_KEY'],
                    'razorpay.key_secret' => $paymentMethod->configs['API_SECRET'],
                    'razorpay.enabled' => $paymentMethod->active,
                ];

            case self::GOCARDLESS:
                $environment = $paymentMethod->test_mode ? 'sandbox' : 'live';
                return [
                    'gocardless.id' => $paymentMethod->id,
                    'gocardless.environment' => $environment,
                    'gocardless.access_token' => $paymentMethod->configs['ACCESS_TOKEN'],
                    'gocardless.webhook_secret' => $paymentMethod->configs['WEBHOOK_SECRET'],
                    'gocardless.webhook_url' => $paymentMethod->webhook,
                    'gocardless.schemes' => [
                        'GB' => 'bacs',      // UK
                        'DE' => 'sepa_core', // Germany
                        'FR' => 'sepa_core', // France
                        'ES' => 'sepa_core', // Spain
                        'IT' => 'sepa_core', // Italy
                        'NL' => 'sepa_core', // Netherlands
                        'BE' => 'sepa_core', // Belgium
                        'AU' => 'becs',      // Australia
                        'NZ' => 'becs_nz',   // New Zealand
                        'US' => 'ach',       // USA
                        'CA' => 'pad',       // Canada
                        'SE' => 'autogiro',  // Sweden
                    ],
                    'gocardless.enabled' => $paymentMethod->active,
                ];

            case self::KLARNA:
                return [
                    'klarna.id' => $paymentMethod->id,
                    'klarna.api_key' => $paymentMethod->configs['API_KEY'],
                    'klarna.api_secret' => $paymentMethod->configs['API_SECRET'],
                    'klarna.webhook_url' => $paymentMethod->webhook,
                    'klarna.test_mode' => $paymentMethod->test_mode,
                    'klarna.enabled' => $paymentMethod->active,
                ];

            case self::MERCADOPAGO:
                return [
                    'mercadopago.id' => $paymentMethod->id,
                    'mercadopago.public_key' => $paymentMethod->configs['PUBLIC_KEY'],
                    'mercadopago.access_token' => $paymentMethod->configs['ACCESS_TOKEN'],
                    'mercadopago.webhook_url' => $paymentMethod->webhook,
                    'mercadopago.test_mode' => $paymentMethod->test_mode,
                    'mercadopago.enabled' => $paymentMethod->active,
                ];

            case self::PAYSTACK:
                return [
                    'paystack.id' => $paymentMethod->id,
                    'paystack.public_key' => $paymentMethod->configs['PUBLIC_KEY'],
                    'paystack.secret_key' => $paymentMethod->configs['SECRET_KEY'],
                    'paystack.webhook_url' => $paymentMethod->webhook,
                    'paystack.test_mode' => $paymentMethod->test_mode,
                    'paystack.enabled' => $paymentMethod->active,
                ];

            case self::XENDIT:
                return [
                    'xendit.id' => $paymentMethod->id,
                    'xendit.public_key' => $paymentMethod->configs['PUBLIC_KEY'],
                    'xendit.secret_key' => $paymentMethod->configs['SECRET_KEY'],
                    'xendit.webhook_url' => $paymentMethod->webhook,
                    'xendit.test_mode' => $paymentMethod->test_mode,
                    'xendit.enabled' => $paymentMethod->active,
                ];

            case self::FLUTTERWAVE:
                return [
                    'flutterwave.id' => $paymentMethod->id,
                    'flutterwave.client_id' => $paymentMethod->configs['CLIENT_ID'],
                    'flutterwave.client_secret' => $paymentMethod->configs['CLIENT_SECRET'],
                    'flutterwave.encryption_key' => $paymentMethod->configs['ENCRYPTION_KEY'],
                    'flutterwave.webhook_url' => $paymentMethod->webhook,
                    'flutterwave.test_mode' => $paymentMethod->test_mode,
                    'flutterwave.enabled' => $paymentMethod->active,
                ];

            case self::APPLE_PAY:
                // Apple Pay is integrated via Stripe, so no direct config needed
                return [
                    'apple_pay.id' => $paymentMethod->id,
                    'apple_pay.integration_via' => $paymentMethod->integration_via,
                    'apple_pay.enabled' => $paymentMethod->active,
                ];

            case self::GOOGLE_PAY:
                // Google Pay is integrated via Stripe, so no direct config needed
                return [
                    'google_pay.id' => $paymentMethod->id,
                    'google_pay.integration_via' => $paymentMethod->integration_via,
                    'google_pay.enabled' => $paymentMethod->active,
                ];

            default:
                return null;
        }
    }

    /**
     * Apply a specific provider's configuration from cache
     *
     * @param string $provider
     * @return void
     */
    public static function applyProviderConfig(string $provider): void
    {
        $allConfigs = Cache::get(self::cacheKey(), []);

        if (isset($allConfigs[$provider]) && !empty($allConfigs[$provider])) {
            config($allConfigs[$provider]);
        }
    }

    /**
     * Apply all provider configurations from cache
     *
     * @param array $configs
     * @return void
     */
    public static function applyConfig(array $configs): void
    {
        foreach ($configs as $providerConfig) {
            if (!empty($providerConfig)) {
                config($providerConfig);
            }
        }
    }

    /**
     * Synchronize payment provider configurations from database to application config
     *
     * @return void
     */
    public static function syncConfig(): void
    {
        try {
            // Use rememberForever for efficient caching
            $configs = Cache::rememberForever(self::cacheKey(), function () {
                $allConfigs = [];

                // Fetch all active payment methods in a single query
                $providers = self::enabled()
                    ->where('provider', '<>', self::MANUAL) // Exclude manual payment methods
                    ->get()
                    ->keyBy('provider')
                    ->keys();

                foreach ($providers as $provider) {
                    $allConfigs[$provider] = self::getProviderConfig($provider);
                }

                return $allConfigs;
            });

            // Apply all configurations
            self::applyConfig($configs);
        } catch (\Exception $e) {
            // Log the error instead of just re-throwing
            Log::error("Failed to synchronize payment provider configurations: {$e->getMessage()}");
        }
    }
}
