<?php

namespace Coderstm\Models\Task;

use Coderstm\Enum\AppStatus;
use Coderstm\Models\Admin;
use Coderstm\Models\Task;
use Coderstm\Traits\Fileable;
use Coderstm\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reply extends Model
{
    use Fileable, HasFactory, SerializeDate;

    protected $table = 'task_replies';

    protected $fillable = ['message', 'task_id', 'user_id'];

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
            $model->task->update(['status' => AppStatus::ONGOING]);
        });
    }
}
