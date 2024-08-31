<?php

namespace Coderstm\Models;

use Coderstm\Coderstm;
use Coderstm\Database\Factories\TaskFactory;
use Coderstm\Traits\Core;
use Coderstm\Enum\AppStatus;
use Coderstm\Traits\Fileable;
use Coderstm\Traits\TaskUser;
use Coderstm\Models\Task\Reply;
use Coderstm\Events\TaskCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Task extends Model
{
    use Core, Fileable, TaskUser;

    // TODO: Add Task Update Notification
    protected $dispatchesEvents = [
        'created' => TaskCreated::class,
    ];

    protected $dateTimeFormat = 'd M, Y \a\t h:i a';

    protected $fillable = [
        'subject',
        'message',
        'status',
        'user_id',
    ];

    protected $appends = ['is_archived'];

    protected $with = [
        'last_reply.user',
        'user',
        'users',
    ];

    protected $casts = [
        'status' => AppStatus::class,
    ];

    public function replies()
    {
        return $this->hasMany(Reply::class, 'task_id')
            ->orderBy('created_at', 'desc');
    }

    public function last_reply()
    {
        return $this->hasOne(Reply::class, 'task_id')
            ->orderBy('created_at', 'desc');
    }

    public function archives(): BelongsToMany
    {
        return $this->belongsToMany(Coderstm::$adminModel, 'task_archives', 'task_id', 'user_id');
    }

    public function getIsArchivedAttribute()
    {
        if ($this->archives->count()) {
            return true;
        }
        return false;
    }

    public function createReply(array $attributes = [])
    {
        return $this->replies()->create($attributes);
    }

    public function getUsers()
    {
        return implode(', ', $this->users->map(function ($user) {
            return $user->name;
        })->all());
    }

    public function scopeOnlyOwner($query)
    {
        if (user()->is_supper_admin) {
            return $query;
        }
        return $query->whereHas('user', function ($q) {
            $q->where('id', user()->id);
        })->orWhereHas('users', function ($q) {
            $q->where('id', user()->id);
        });
    }

    public function scopeOnlyActive($query)
    {
        return $query->doesntHave('archives');
    }

    public function scopeOnlyArchived($query)
    {
        return $query->has('archives');
    }

    public function scopeOnlyStatus($query, $status = null)
    {
        switch ($status) {
            case 'Live':
                return $query->onlyActive();
                break;

            case 'Archive':
                return $query->onlyArchived();
                break;
        }

        return $query;
    }

    public function scopeSortBy($query, $column = 'created_at', $direction = 'asc')
    {
        switch ($column) {
            case 'last_reply':
                return $query->select("tasks.*")
                    ->leftJoin('task_replies', function ($join) {
                        $join->on('task_replies.task_id', '=', "tasks.id");
                    })
                    ->groupBy("tasks.id")
                    ->orderBy(DB::raw('task_replies.created_at IS NULL'), 'desc')
                    ->orderBy(DB::raw('task_replies.created_at'), $direction ?? 'asc');
                break;

            default:
                return $query->orderBy($column ?: 'created_at', $direction ?? 'asc');
                break;
        }
    }

    public function getShortCodes(): array
    {
        $attachments = '';

        if (count($this->media)) {
            $attachments = "<p><b><small>Attachments</small></b>:<br>";
            foreach ($this->media as $media) {
                $attachments .= "<small><svg style=\"width:10px\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 512\"><path d=\"M396.2 83.8c-24.4-24.4-64-24.4-88.4 0l-184 184c-42.1 42.1-42.1 110.3 0 152.4s110.3 42.1 152.4 0l152-152c10.9-10.9 28.7-10.9 39.6 0s10.9 28.7 0 39.6l-152 152c-64 64-167.6 64-231.6 0s-64-167.6 0-231.6l184-184c46.3-46.3 121.3-46.3 167.6 0s46.3 121.3 0 167.6l-176 176c-28.6 28.6-75 28.6-103.6 0s-28.6-75 0-103.6l144-144c10.9-10.9 28.7-10.9 39.6 0s10.9 28.7 0 39.6l-144 144c-6.7 6.7-6.7 17.7 0 24.4s17.7 6.7 24.4 0l176-176c24.4-24.4 24.4-64 0-88.4z\"/></svg><a href=\"{$media->url}\">{$media->name}</a></small><br>";
            }
            $attachments .= "</p>";
        }

        return [
            '{{TASK_ID}}' => $this->id,
            '{{TASK_URL}}' => admin_url("tasks/{$this->id}?action=edit"),
            '{{ADMIN_TASK_URL}}' => app_url("tasks/{$this->id}?action=edit"),
            '{{TASK_ATTACHMENTS}}' => $attachments,
            '{{TASK_SUBJECT}}' => $this->subject,
            '{{TASK_MESSAGE}}' => $this->message,
            '{{TASK_CREATED_BY}}' => $this->user->name,
        ];
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return TaskFactory::new();
    }

    protected static function booted()
    {
        parent::booted();
        static::creating(function ($model) {
            if (empty($model->status)) {
                $model->status = AppStatus::PENDING->value;
            }
            if (empty($model->user_id)) {
                $model->user_id = optional(user())->id;
            }
        });
    }
}
