<?php

namespace Coderstm\Models;

use Coderstm\Coderstm;
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
        return $this->hasMany(Reply::class, 'task_id')->orderBy('created_at', 'desc');
    }

    public function last_reply()
    {
        return $this->hasOne(Reply::class, 'task_id')->latestOfMany();
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
        if (current_user()->is_supper_admin) {
            return $query;
        }
        return $query->whereHas('user', function ($q) {
            $q->where('id', current_user()->id);
        })->orWhereHas('users', function ($q) {
            $q->where('id', current_user()->id);
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

    protected static function booted()
    {
        parent::booted();
        static::creating(function ($model) {
            if (empty($model->status)) {
                $model->status = AppStatus::PENDING->value;
            }
            if (empty($model->user_id)) {
                $model->user_id = optional(current_user())->id;
            }
        });
    }
}
