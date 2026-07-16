<?php

namespace Coderstm\Policies\Subscription;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model as Plan;

class PlanPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     *
     * @param  mixed  $admin
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
     * @param  mixed  $admin
     */
    public function viewAny($admin): bool
    {
        return $admin->canAny(['plans:read', 'plans:write', 'plans:editor']);
    }

    /**
     * Determine whether the admin can view the model.
     *
     * @param  mixed  $admin
     * @param  Plan  $plan
     */
    public function view($admin, $plan): bool
    {
        return $admin->canAny(['plans:read', 'plans:write', 'plans:editor']);
    }

    /**
     * Determine whether the admin can create models.
     *
     * @param  mixed  $admin
     */
    public function create($admin): bool
    {
        return $admin->canAny(['plans:write', 'plans:editor']);
    }

    /**
     * Determine whether the admin can update the model.
     *
     * @param  mixed  $admin
     * @param  Plan  $plan
     */
    public function update($admin, $plan): bool
    {
        return $admin->canAny(['plans:write', 'plans:editor']) && ($plan->user_id == $admin->id || $plan->hasUser($admin->id));
    }

    /**
     * Determine whether the admin can delete the model.
     *
     * @param  mixed  $admin
     */
    public function delete($admin): bool
    {
        return $admin->can('plans:write');
    }

    /**
     * Determine whether the admin can restore the model.
     *
     * @param  mixed  $admin
     */
    public function restore($admin): bool
    {
        return $admin->can('plans:write');
    }

    /**
     * Determine whether the admin can permanently delete the model.
     *
     * @param  mixed  $admin
     */
    public function forceDelete($admin): bool
    {
        return $admin->can('plans:write');
    }
}
