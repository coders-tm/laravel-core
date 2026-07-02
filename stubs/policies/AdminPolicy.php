<?php

namespace App\Policies;

use App\Models\Admin;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdminPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     *
     * @param  mixed  $admin
     * @param  mixed  $ability
     * @return mixed
     */
    public function before($admin, $ability)
    {
        if ($admin->is_supper_admin) {
            return true;
        }
    }

    /**
     * Determine whether the admin can view any models.
     *
     * @param  mixed  $admin
     * @return bool|mixed
     */
    public function viewAny($admin)
    {
        return $admin->can('staff:list');
    }

    /**
     * Determine whether the admin can view the model.
     *
     * @param  mixed  $admin
     * @return bool|mixed
     */
    public function view($admin)
    {
        return $admin->can('staff:view');
    }

    /**
     * Determine whether the admin can create models.
     *
     * @param  mixed  $admin
     * @return bool|mixed
     */
    public function create($admin)
    {
        return $admin->can('staff:new');
    }

    /**
     * Determine whether the admin can update the model.
     *
     * @param  mixed  $admin
     * @return bool|mixed
     */
    public function update($admin)
    {
        return $admin->can('staff:edit');
    }

    /**
     * Determine whether the admin can delete the model.
     *
     * @param  mixed  $admin
     * @return bool|mixed
     */
    public function delete($admin)
    {
        return $admin->can('staff:delete');
    }

    /**
     * Determine whether the admin can restore the model.
     *
     * @param  mixed  $admin
     * @return bool|mixed
     */
    public function restore($admin)
    {
        return $admin->can('staff:restore');
    }

    /**
     * Determine whether the admin can permanently delete the model.
     *
     * @param  mixed  $admin
     * @return bool|mixed
     */
    public function forceDelete($admin)
    {
        return $admin->can('staff:forceDelete');
    }
}
