<?php

namespace Coderstm\Models;

use Coderstm\Coderstm;
use Coderstm\Traits\Fileable;
use Coderstm\Events\LogCreated;
use Coderstm\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Log extends Model
{
    use HasFactory, Fileable, SerializeDate;

    const STATUS_ERROR = 'error';
    const STATUS_SUCCESS = 'success';
    const STATUS_WARNING = 'warning';

    protected $dispatchesEvents = [
        'created' => LogCreated::class,
    ];

    protected $fillable = [
        'type',
        'status',
        'message',
        'options',
        'admin_id',
    ];

    protected $dateTimeFormat = 'd M, Y \a\t h:i a';

    protected $hidden = [
        'logable_type',
        'logable_id',
    ];

    protected $appends = [
        'date_time',
        'can_edit',
        'created_at_human',
    ];

    protected $casts = [
        'options' => 'json',
    ];

    public function getDateTimeAttribute()
    {
        return $this->created_at->format($this->dateTimeFormat);
    }

    public function getCreatedAtHumanAttribute()
    {
        return $this->created_at->diffForHumans();
    }

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
