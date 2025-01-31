<?php

namespace Coderstm\Models;

use Coderstm\Traits\Core;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    use Core;

    protected $fillable = [
        'key',
        'options'
    ];

    protected $casts = [
        'options' => 'collection',
    ];

    static public function create($key, array $options = [])
    {
        return static::updateValue($key, $options);
    }

    static public function updateOptions($key, array $options = [], $merge = true)
    {
        if ($merge) {
            return static::updateValue($key, $options);
        }

        return static::updateOrCreate([
            'key' => $key
        ], [
            'options' => $options
        ]);
    }

    static public function updateValue($key, array $options = [])
    {
        $original = static::findByKey($key);
        return static::updateOrCreate([
            'key' => $key
        ], [
            'options' => $original->merge($options)
        ]);
    }

    static public function findByKey(string $key): Collection
    {
        try {
            $result = static::where('key', $key)->first();
            return $result->options ?? collect();
        } catch (\Exception $e) {
            return collect();
        }
    }

    static public function value(string $key, string $attribute)
    {
        return static::findByKey($key)->get($attribute);
    }
}
