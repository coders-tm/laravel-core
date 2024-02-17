<?php

namespace Coderstm\Policies;

use Coderstm\Models\Plan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlanPolicy
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
        return $admin->can('plans:list');
    }

    /**
     * Determine whether the admin can view the model.
     */
    public function view(Model $admin, Plan $plan): bool
    {
        return $admin->can('plans:view') && ($plan->user_id == $admin->id || $plan->hasUser($admin->id));
    }

    /**
     * Determine whether the admin can create models.
     */
    public function create(Model $admin): bool
    {
        return $admin->can('plans:new');
    }

    /**
     * Determine whether the admin can update the model.
     */
    public function update(Model $admin, Plan $plan): bool
    {
        return $admin->can('plans:edit') && ($plan->user_id == $admin->id || $plan->hasUser($admin->id));
    }

    /**
     * Determine whether the admin can delete the model.
     */
    public function delete(Model $admin): bool
    {
        return $admin->can('plans:delete');
    }

    /**
     * Determine whether the admin can restore the model.
     */
    public function restore(Model $admin): bool
    {
        return $admin->can('plans:restore');
    }

    /**
     * Determine whether the admin can permanently delete the model.
     */
    public function forceDelete(Model $admin): bool
    {
        return $admin->can('plans:forceDelete');
    }
}
