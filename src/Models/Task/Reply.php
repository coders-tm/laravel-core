<?php

namespace Coderstm\Models\Task;

use Coderstm\Models\Task;
use Coderstm\Models\Admin;
use Coderstm\Enum\AppStatus;
use Coderstm\Traits\Fileable;
use Coderstm\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Coderstm\Database\Factories\ReplyFactory;
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
                $model->user_id = user()->id ?? null;
            }
        });
        static::created(function ($model) {
            $model->task->update([
                'status' => AppStatus::ONGOING
            ]);
        });
    }
}
