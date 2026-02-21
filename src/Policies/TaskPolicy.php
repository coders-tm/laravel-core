<?php

namespace Coderstm\Policies;

use Coderstm\Models\Task;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

class TaskPolicy
{
    use HandlesAuthorization;

    public function before(Model $admin, string $ability)
    {
        if ($admin->is_supper_admin) {
            return true;
        }
    }

    public function viewAny(Model $admin): bool
    {
        return $admin->can('tasks:list');
    }

    public function view(Model $admin, Task $task): bool
    {
        return $admin->can('tasks:view') && ($task->user_id == $admin->id || $task->hasUser($admin->id));
    }

    public function create(Model $admin): bool
    {
        return $admin->can('tasks:new');
    }

    public function update(Model $admin, Task $task): bool
    {
        return $admin->can('tasks:edit') && ($task->user_id == $admin->id || $task->hasUser($admin->id));
    }

    public function delete(Model $admin): bool
    {
        return $admin->can('tasks:delete');
    }

    public function restore(Model $admin): bool
    {
        return $admin->can('tasks:restore');
    }

    public function forceDelete(Model $admin): bool
    {
        return $admin->can('tasks:forceDelete');
    }
}
