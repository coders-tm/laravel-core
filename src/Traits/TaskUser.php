<?php

namespace Coderstm\Traits;

use Coderstm\Coderstm;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait TaskUser
{
    /**
     * Get the user that owns the Task
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(Coderstm::$adminModel, 'user_id');
    }

    /**
     * The users that belong to the Task
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(Coderstm::$adminModel, 'task_users', 'task_id', 'user_id')->withOnly([]);
    }

    public function syncUsers(Collection $users, bool $detach = true)
    {
        $users = $users->pluck('id');
        if ($detach) {
            $this->users()->sync($users);
        } else {
            $this->users()->syncWithoutDetaching($users);
        }
        return $this;
    }

    public function syncUsersDetaching(Collection $users)
    {
        return $this->syncUsers($users, false);
    }

    public function hasUser(...$users)
    {
        foreach ($users as $user) {
            if ($this->users->contains('id', $user)) {
                return true;
            }
        }
        return false;
    }
}
