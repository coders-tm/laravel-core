<?php

namespace Coderstm\Models;

use Coderstm\Traits\Core;
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
        return static::updateOrCreate([
            'key' => $key
        ], [
            'options' => $options
        ]);
    }

    static public function updateValue($key, array $options = [])
    {
        $oldValue = static::findByKey($key);
        return static::updateOrCreate([
            'key' => $key
        ], [
            'options' => $oldValue->merge($options)
        ]);
    }

    static public function findByKey(string $key)
    {
        $result = static::where('key', $key)->first();
        return $result ? $result->options : collect([]);
    }
}
