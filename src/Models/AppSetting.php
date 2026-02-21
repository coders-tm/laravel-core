<?php

namespace Coderstm\Models;

use Coderstm\Traits\Core;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class AppSetting extends Model
{
    use Core;

    protected $fillable = ['key', 'options'];

    protected $casts = ['options' => 'array'];

    protected $logIgnore = ['options'];

    protected static $cacheKey = 'coderstm_app_settings';

    protected static function getOverrideMap(): array
    {
        return config('coderstm.settings_override', ['config' => ['alias' => 'app', 'email' => ['coderstm.admin_email', 'mail.from.address'], 'name' => ['mail.from.name'], 'currency' => 'cashier.currency', 'timezone' => fn ($value) => date_default_timezone_set($value)]]);
    }

    public static function create($key, array $options = [])
    {
        $result = static::updateValue($key, $options);
        static::clearCache();

        return $result;
    }

    protected static function parseDottedKey(string $key): array
    {
        $segments = explode('.', $key);
        $dbKey = array_shift($segments);

        return ['key' => $dbKey, 'path' => $segments];
    }

    protected static function setNestedValue(array $array, array $path, $value): array
    {
        if (empty($path)) {
            return is_array($value) ? $value : $array;
        }
        $current = &$array;
        foreach ($path as $segment) {
            if (! isset($current[$segment]) || ! is_array($current[$segment])) {
                $current[$segment] = [];
            }
            $current = &$current[$segment];
        }
        if (is_array($value)) {
            $current = array_merge($current, $value);
        } else {
            $current = $value;
        }

        return $array;
    }

    public static function updateOptions($key, array $options = [])
    {
        return static::updateValue($key, $options);
    }

    public static function updateValue($key, array $options = [], bool $replace = false)
    {
        $parsed = static::parseDottedKey($key);
        $dbKey = $parsed['key'];
        $path = $parsed['path'];
        $original = $replace ? [] : static::findByKey($dbKey);
        if (! empty($path)) {
            $newOptions = static::setNestedValue($original, $path, $options);
        } else {
            $newOptions = array_merge($original, $options);
        }
        $filteredOptions = static::filterEmptyArrays($newOptions);
        $model = static::updateOrCreate(['key' => $dbKey], ['options' => $filteredOptions]);
        if (Cache::has(static::$cacheKey)) {
            $cachedSettings = Cache::get(static::$cacheKey);
            $cachedSettings = is_array($cachedSettings) ? $cachedSettings : [];
            $cachedSettings[$dbKey] = $model->options;
            Cache::put(static::$cacheKey, $cachedSettings);
        }
        static::applyConfigOverrides($dbKey, $model->options);

        return $model->options;
    }

    protected static function filterEmptyArrays(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $filtered = static::filterEmptyArrays($value);
                if (! empty($filtered)) {
                    $result[$key] = $filtered;
                }
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    protected static function applyConfigOverrides(string $key, array $options): void
    {
        try {
            $overrideMap = static::getOverrideMap();
            if (! isset($overrideMap[$key])) {
                return;
            }
            $mappingRules = $overrideMap[$key];
            $configAlias = $mappingRules['alias'] ?? $key;
            static::applyConfigMapping($configAlias, $options, $mappingRules);
        } catch (\Throwable $e) {
            Log::error("Failed to apply config overrides: {$e->getMessage()}");
        }
    }

    public static function syncConfig(): void
    {
        try {
            $overrideMap = static::getOverrideMap();
            $allSettings = self::getSettings();
            foreach ($allSettings as $settingKey => $settingValues) {
                if (! is_iterable($settingValues)) {
                    continue;
                }
                $mappingRules = $overrideMap[$settingKey] ?? [];
                $configAlias = $mappingRules['alias'] ?? $settingKey;
                static::applyConfigMapping($configAlias, $settingValues, $mappingRules);
            }
        } catch (\Throwable $e) {
            Log::error("Failed to sync application settings to config: {$e->getMessage()}");
        }
    }

    protected static function applyConfigMapping(string $configAlias, $values, array $mappingRules): void
    {
        foreach ($values as $property => $value) {
            Config::set("{$configAlias}.{$property}", $value);
            if (! isset($mappingRules[$property])) {
                continue;
            }
            $mapping = $mappingRules[$property];
            if (is_array($mapping)) {
                foreach ($mapping as $configKey) {
                    Config::set($configKey, $value);
                }
            } elseif (is_callable($mapping)) {
                $mapping($value);
            } else {
                Config::set($mapping, $value);
            }
        }
    }

    public static function find(string $key): array
    {
        return static::findByKey($key);
    }

    public static function findByKey(string $key): array
    {
        try {
            $settings = static::getSettings();
            if (is_array($settings) && isset($settings[$key])) {
                $value = $settings[$key];

                return is_array($value) ? $value : [];
            }

            return [];
        } catch (\Throwable $e) {
            Log::error("Error in findByKey: {$e->getMessage()}");

            return [];
        }
    }

    public static function findByKeyAsCollection(string $key): \Illuminate\Support\Collection
    {
        return collect(static::findByKey($key));
    }

    public static function value(string $key, string $attribute, $default = null)
    {
        $options = static::findByKey($key);

        return $options[$attribute] ?? $default;
    }

    public static function getSettings(): array
    {
        $settings = Cache::remember(static::$cacheKey, 5 * 60, function () {
            return static::all()->keyBy('key')->map(function ($item) {
                return $item->options;
            })->toArray();
        });

        return $settings;
    }

    public static function get(string $key, $default = null)
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

    public static function clearCache()
    {
        Cache::forget(static::$cacheKey);
    }
}
