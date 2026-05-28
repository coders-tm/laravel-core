<?php

namespace App\Policies;

use App\Models\Admin;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdminPolicy
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
        return $admin->can('staff:list');
    }

    /**
     * Determine whether the admin can view the model.
     */
    public function view(Admin $admin)
    {
        return $admin->can('staff:view');
    }

    /**
     * Determine whether the admin can create models.
     */
    public function create(Admin $admin)
    {
        return $admin->can('staff:new');
    }

    /**
     * Determine whether the admin can update the model.
     */
    public function update(Admin $admin)
    {
        return $admin->can('staff:edit');
    }

    /**
     * Determine whether the admin can delete the model.
     */
    public function delete(Admin $admin)
    {
        return $admin->can('staff:delete');
    }

    /**
     * Determine whether the admin can restore the model.
     */
    public function restore(Admin $admin)
    {
        return $admin->can('staff:restore');
    }

    /**
     * Determine whether the admin can permanently delete the model.
     */
    public function forceDelete(Admin $admin)
    {
        return $admin->can('staff:forceDelete');
    }
}
