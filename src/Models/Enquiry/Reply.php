<?php

namespace Coderstm\Models\Enquiry;

use Coderstm\Coderstm;
use Coderstm\Enum\AppStatus;
use Coderstm\Traits\Fileable;
use Coderstm\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Reply extends Model
{
    use HasFactory, Fileable, SerializeDate;

    protected $fillable = [
        'message',
        'enquiry_id',
        'user_type',
        'user_id',
        'seen',
        'staff_only',
    ];

    protected $with = ['media'];

    protected $casts = [
        'seen' => 'boolean',
    ];

    protected $appends = ['created_time'];

    protected $dateTimeFormat = 'd M, Y \a\t h:i a';

    public function getCreatedTimeAttribute()
    {
        return $this->created_at->format('H:i');
    }

    public function enquiry()
    {
        return $this->belongsTo(Coderstm::$enquiryModel);
    }

    public function user()
    {
        return $this->morphTo()->withOnly([]);
    }

    public function scopeUnseen($query)
    {
        return $query->where('seen', 0);
    }

    protected static function booted()
    {
        parent::booted();
        static::creating(function ($model) {
            if ($model->user_type == 'Admin') {
                $model->seen = true;
            }
        });
        static::created(function ($model) {
            if ($model->staff_only) {
                return false;
            }
            if ($model->user_type == 'Admin') {
                $model->enquiry->update([
                    'status' => AppStatus::STAFF_REPLIED,
                    'user_archived' => 0
                ]);
            } else {
                $model->enquiry->update([
                    'status' => AppStatus::REPLIED,
                    'is_archived' => 0
                ]);
            }
        });
        static::addGlobalScope('default', function (Builder $builder) {
            if (is_user()) {
                $builder->where('staff_only', 0);
            }
        });
    }
}
