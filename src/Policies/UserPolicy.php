<?php

namespace Coderstm\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model as Admin;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     */
    public function before(Admin $admin, $ability)
    {
        if ($admin->is_supper_admin) {
            return true;
        }
    }

    /**
     * Determine whether the admin can view any models.
     */
    public function viewAny(Admin $admin)
    {
        return $admin->can('members:list');
    }

    /**
     * Determine whether the admin can view the model.
     */
    public function view(Admin $admin, $user)
    {
        if (is_user()) {
            return $user->id == user()->id;
        }

        return $admin->can('members:view');
    }

    /**
     * Determine whether the admin can create models.
     */
    public function create(Admin $admin)
    {
        return $admin->can('members:new');
    }

    /**
     * Determine whether the admin can update the model.
     */
    public function update(Admin $admin)
    {
        return $admin->can('members:edit');
    }

    /**
     * Determine whether the admin can delete the model.
     */
    public function delete(Admin $admin)
    {
        return $admin->can('members:delete');
    }

    /**
     * Determine whether the admin can restore the model.
     */
    public function restore(Admin $admin)
    {
        return $admin->can('members:restore');
    }

    /**
     * Determine whether the admin can permanently delete the model.
     */
    public function forceDelete(Admin $admin)
    {
        return $admin->can('members:forceDelete');
    }
}
