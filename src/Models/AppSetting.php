<?php

namespace Coderstm\Models;

use Coderstm\Traits\Core;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    use Core;

    protected $fillable = [
        'key',
        'options'
    ];

    protected $casts = [
        'options' => 'array',
    ];

    /**
     * Cache key for all settings
     */
    protected static $cacheKey = 'coderstm_app_settings';

    /**
     * Get the default override map with all handlers
     *
     * @return array
     */
    protected static function getOverrideMap(): array
    {
        return config('coderstm.settings_override', [
            'config' => [
                'alias' => 'app',
                'email' => [
                    'coderstm.admin_email',
                    'mail.from.address',
                ],
                'name' => ['mail.from.name'],
                'currency' => 'cashier.currency',
                'timezone' => fn($value) => date_default_timezone_set($value),
            ]
        ]);
    }

    /**
     * Create a new setting and return only its options
     *
     * @param string $key
     * @param array $options
     * @return array
     */
    static public function create($key, array $options = [])
    {
        $result = static::updateValue($key, $options);
        static::clearCache();
        return $result;
    }

    static public function updateOptions($key, array $options = [], $merge = true)
    {
        if ($merge) {
            return static::updateValue($key, $options);
        }

        $result = static::updateOrCreate([
            'key' => $key
        ], [
            'options' => $options
        ]);

        static::clearCache();
        return $result;
    }

    /**
     * Update a setting value and return only its options
     *
     * @param string $key
     * @param array $options
     * @return array
     */
    static public function updateValue($key, array $options = [])
    {
        $original = static::findByKey($key);
        $model = static::updateOrCreate([
            'key' => $key
        ], [
            'options' => array_merge($original, $options)
        ]);

        // Update cache efficiently for just this key instead of clearing all
        if (Cache::has(static::$cacheKey)) {
            $cachedSettings = Cache::get(static::$cacheKey);
            // Ensure we're working with an array
            $cachedSettings = is_array($cachedSettings) ? $cachedSettings : [];
            $cachedSettings[$key] = $model->options;
            Cache::put(static::$cacheKey, $cachedSettings);
        }

        // Apply config overrides immediately for this specific key
        static::applyConfigOverrides($key, $model->options);

        return $model->options;
    }

    /**
     * Apply config overrides for a specific setting key
     *
     * @param string $key
     * @param array $options
     * @return void
     */
    protected static function applyConfigOverrides(string $key, array $options): void
    {
        try {
            $overrideMap = static::getOverrideMap();

            if (!isset($overrideMap[$key])) {
                return;
            }

            $mappingRules = $overrideMap[$key];
            $configAlias = $mappingRules['alias'] ?? $key;

            // Apply mappings for this key/options pair
            static::applyConfigMapping($configAlias, $options, $mappingRules);
        } catch (\Exception $e) {
            Log::error("Failed to apply config overrides: {$e->getMessage()}");
        }
    }

    /**
     * Synchronize application settings with Laravel configuration
     *
     * This method maps stored application settings to Laravel's config system,
     * applying all override rules defined in the configuration.
     *
     * @return void
     */
    public static function syncConfig(): void
    {
        try {
            // Get the configuration mapping from settings to Laravel config
            $overrideMap = static::getOverrideMap();

            // Load all settings at once to reduce database calls
            $allSettings = self::getSettings();

            // Process each setting group (app, mail, etc)
            foreach ($allSettings as $settingKey => $settingValues) {
                // Skip if not a collection/array
                if (!is_iterable($settingValues)) {
                    continue;
                }

                // Get mapping rules for this setting key
                $mappingRules = $overrideMap[$settingKey] ?? [];
                $configAlias = $mappingRules['alias'] ?? $settingKey;

                // Apply mappings for this key/values pair
                static::applyConfigMapping($configAlias, $settingValues, $mappingRules);
            }
        } catch (\Exception $e) {
            // Log the error instead of just re-throwing
            Log::error("Failed to sync application settings to config: {$e->getMessage()}");
        }
    }

    /**
     * Apply configuration mappings based on rules
     *
     * @param string $configAlias Base config key
     * @param array|iterable $values Values to apply
     * @param array $mappingRules Rules for mapping values to config keys
     * @return void
     */
    protected static function applyConfigMapping(string $configAlias, $values, array $mappingRules): void
    {
        foreach ($values as $property => $value) {
            // Always set the value in its original location
            Config::set("$configAlias.$property", $value);

            // Skip if no special mapping exists
            if (!isset($mappingRules[$property])) {
                continue;
            }

            $mapping = $mappingRules[$property];

            // Handle different mapping types
            if (is_array($mapping)) {
                // Map to multiple config keys
                foreach ($mapping as $configKey) {
                    Config::set($configKey, $value);
                }
            } elseif (is_callable($mapping)) {
                // Execute custom logic
                $mapping($value);
            } else {
                // Direct mapping to a different config key
                Config::set($mapping, $value);
            }
        }
    }

    /**
     * Find settings by key and return as array
     *
     * @param string $key
     * @return array
     */
    static public function find(string $key): array
    {
        return static::findByKey($key);
    }

    /**
     * Find settings by key and return as array
     *
     * @param string $key
     * @return array
     */
    static public function findByKey(string $key): array
    {
        try {
            $settings = static::getSettings();

            // Simple array access
            if (is_array($settings) && isset($settings[$key])) {
                $value = $settings[$key];
                return is_array($value) ? $value : [];
            }

            return [];
        } catch (\Exception $e) {
            Log::error("Error in findByKey: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Find settings by key and return as a Collection
     *
     * This method provides backward compatibility with code expecting a Collection.
     *
     * @param string $key
     * @return \Illuminate\Support\Collection
     */
    static public function findByKeyAsCollection(string $key): \Illuminate\Support\Collection
    {
        return collect(static::findByKey($key));
    }

    static public function value(string $key, string $attribute, $default = null)
    {
        $options = static::findByKey($key);
        return $options[$attribute] ?? $default;
    }

    /**
     * Get all settings from cache or database
     *
     * @return array
     */
    static public function getSettings(): array
    {
        $settings = Cache::remember(static::$cacheKey, 5 * 60, function () {
            return static::all()->keyBy('key')
                ->map(function ($item) {
                    return $item->options;
                })
                ->toArray();
        });

        return $settings;
    }

    /**
     * Get a specific setting value with dot notation support
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    static public function get(string $key, $default = null)
    {
        $segments = explode('.', $key);
        $settingKey = array_shift($segments);

        $options = static::findByKey($settingKey);

        if (empty($options)) {
            return $default;
        }

        if (empty($segments)) {
            return empty($options) ? $default : $options;
        }

        $value = $options;
        foreach ($segments as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * Clear settings cache
     *
     * @return void
     */
    static public function clearCache()
    {
        Cache::forget(static::$cacheKey);
    }
}
