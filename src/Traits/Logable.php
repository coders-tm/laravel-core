<?php

namespace Coderstm\Traits;

use Coderstm\Enum\LogType;
use Coderstm\Models\Log;
use Illuminate\Support\Str;

trait Logable
{
    public function logs()
    {
        return $this->morphMany(Log::class, 'logable')->orderBy('created_at', 'desc');
    }

    public function getDisplayableAttribute($attribute, $attributes = [])
    {
        if (isset($attributes[$attribute])) {
            return $attributes[$attribute];
        }
        return str_replace('_', ' ', Str::snake($attribute));
    }

    protected static function mapLogValue($key, $value)
    {
        return $value;
    }

    public function getLoggable()
    {
        return array_diff($this->fillable, $this->logignore ?? []);
    }

    protected static function boot()
    {
        parent::boot();
        static::created(function ($model) {
            $modelName = model_log_name($model);
            $data = [
                'message' => "{$modelName} has been created.",
            ];
            if (!empty($model->log_options)) {
                $data['options'] = $model->log_options;
            }
            $model->logs()->updateOrCreate([
                'type' => LogType::CREATED,
            ], $data);
        });
        static::deleted(function ($model) {
            $modelName = model_log_name($model);
            $model->logs()->create([
                'type' => LogType::DELETED,
                'message' => "{$modelName} has been deleted.",
            ]);
        });
        static::updated(function ($model) {
            $modelName = model_log_name($model);
            $options = [];
            foreach ($model->getLoggable() as $key) {
                if ($model->wasChanged($key)) {
                    $options[$key] = [
                        'previous' => static::mapLogValue($key, $model->getOriginal($key)),
                        'current' => static::mapLogValue($key, $model[$key]),
                    ];
                }
            }

            if (!empty($options)) {
                $model->logs()->create([
                    'type' => LogType::UPDATED,
                    'message' => "{$modelName} has been updated.",
                    'options' => $options
                ]);
            }

            if (method_exists(static::class, 'customUpdated')) {
                static::customUpdated($model, $modelName);
            }
        });
        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                $modelName = class_basename(get_class($model));
                $model->logs()->create([
                    'type' => LogType::RESTORED,
                    'message' => "{$modelName} has been restored.",
                ]);
            });
        }
    }
}
