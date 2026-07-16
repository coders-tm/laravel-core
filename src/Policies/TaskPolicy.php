<?php

namespace Coderstm\Policies;

use Coderstm\Models\Task;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

class TaskPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     */
    public function before(Model $admin, string $ability)
    {
        if ($admin->is_supper_admin) {
            return true;
        }
    }

    /**
     * Determine whether the admin can view any models.
     */
    public function viewAny(Model $admin): bool
    {
        return $admin->canAny(['tasks:read', 'tasks:write', 'tasks:editor']);
    }

    /**
     * Determine whether the admin can view the model.
     */
    public function view(Model $admin, Task $task): bool
    {
        return $admin->canAny(['tasks:read', 'tasks:write', 'tasks:editor']) && ($task->user_id == $admin->id || $task->hasUser($admin->id));
    }

    /**
     * Determine whether the admin can create models.
     */
    public function create(Model $admin): bool
    {
        return $admin->canAny(['tasks:write', 'tasks:editor']);
    }

    /**
     * Determine whether the admin can update the model.
     */
    public function update(Model $admin, Task $task): bool
    {
        return $admin->canAny(['tasks:write', 'tasks:editor']) && ($task->user_id == $admin->id || $task->hasUser($admin->id));
    }

    /**
     * Determine whether the admin can delete the model.
     */
    public function delete(Model $admin): bool
    {
        return $admin->can('tasks:write');
    }

    /**
     * Determine whether the admin can restore the model.
     */
    public function restore(Model $admin): bool
    {
        return $admin->can('tasks:write');
    }

    /**
     * Determine whether the admin can permanently delete the model.
     */
    public function forceDelete(Model $admin): bool
    {
        return $admin->can('tasks:write');
    }
}
