<?php

namespace Coderstm\Models\Task;

use Coderstm\Enum\AppStatus;
use Coderstm\Models\Admin;
use Coderstm\Traits\Fileable;
use Coderstm\Models\Task;
use Coderstm\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Reply extends Model
{
    use HasFactory, Fileable, SerializeDate;

    protected $table = 'task_replies';

    protected $fillable = [
        'message',
        'task_id',
        'user_id',
    ];

    protected $with = ['media'];

    protected $appends = ['created_time'];

    protected $dateTimeFormat = 'd M, Y \a\t h:i a';

    public function getCreatedTimeAttribute()
    {
        return $this->created_at->format('H:i');
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(Admin::class, 'user_id')->withOnly([]);
    }

    protected static function booted()
    {
        parent::booted();
        static::creating(function ($model) {
            if (empty($model->user_id)) {
                $model->user_id = current_user()->id ?? null;
            }
        });
        static::created(function ($model) {
            $model->task->update([
                'status' => AppStatus::ONGOING
            ]);
        });
    }
}
