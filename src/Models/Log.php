<?php

namespace Coderstm\Models;

use Coderstm\Coderstm;
use Coderstm\Events\LogCreated;
use Coderstm\Traits\Fileable;
use Coderstm\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Log extends Model
{
    use Fileable, HasFactory, SerializeDate;

    const STATUS_ERROR = 'error';

    const STATUS_SUCCESS = 'success';

    const STATUS_WARNING = 'warning';

    protected $dispatchesEvents = ['created' => LogCreated::class];

    protected $fillable = ['type', 'status', 'message', 'options', 'admin_id'];

    protected $hidden = ['logable_type', 'logable_id'];

    protected $appends = ['can_edit'];

    protected $casts = ['options' => 'json'];

    public function logable()
    {
        return $this->morphTo();
    }

    public function admin()
    {
        return $this->belongsTo(Coderstm::$adminModel)->withOnly([]);
    }

    public function reply(): MorphMany
    {
        return $this->morphMany(static::class, 'logable');
    }

    public function getCanEditAttribute()
    {
        return $this->created_at->addMinutes(5)->gt(now());
    }

    public static function error($message, $context = []): void
    {
        \Illuminate\Support\Facades\Log::error($message, $context = []);
    }

    protected static function booted()
    {
        parent::booted();
        static::creating(function ($model) {
            if (empty($model->admin_id) && is_admin()) {
                $model->admin_id = user()->id ?? null;
            }
        });
    }
}
