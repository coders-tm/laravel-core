<?php

namespace Coderstm\Policies\Subscription;

use Illuminate\Database\Eloquent\Model as Plan;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlanPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     *
     * @param mixed $admin
     * @param string $ability
     * @return mixed
     */
    public function before($admin, string $ability)
    {
        if ($admin->is_supper_admin) {
            return true;
        }
    }

    /**
     * Determine whether the admin can view any models.
     *
     * @param mixed $admin
     * @return bool
     */
    public function viewAny($admin): bool
    {
        return $admin->can('plans:list');
    }

    /**
     * Determine whether the admin can view the model.
     *
     * @param mixed $admin
     * @param Plan $plan
     * @return bool
     */
    public function view($admin, $plan): bool
    {
        return $admin->can('plans:view');
    }

    /**
     * Determine whether the admin can create models.
     *
     * @param mixed $admin
     * @return bool
     */
    public function create($admin): bool
    {
        return $admin->can('plans:new');
    }

    /**
     * Determine whether the admin can update the model.
     *
     * @param mixed $admin
     * @param Plan $plan
     * @return bool
     */
    public function update($admin, $plan): bool
    {
        return $admin->can('plans:edit') && ($plan->user_id == $admin->id || $plan->hasUser($admin->id));
    }

    /**
     * Determine whether the admin can delete the model.
     *
     * @param mixed $admin
     * @return bool
     */
    public function delete($admin): bool
    {
        return $admin->can('plans:delete');
    }

    /**
     * Determine whether the admin can restore the model.
     *
     * @param mixed $admin
     * @return bool
     */
    public function restore($admin): bool
    {
        return $admin->can('plans:restore');
    }

    /**
     * Determine whether the admin can permanently delete the model.
     *
     * @param mixed $admin
     * @return bool
     */
    public function forceDelete($admin): bool
    {
        return $admin->can('plans:forceDelete');
    }
}
