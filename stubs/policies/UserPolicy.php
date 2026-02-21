<?php

namespace App\Policies;

use App\Models\Admin;
use Illuminate\Auth\Access\HandlesAuthorization;

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

    /**
     * Determine whether the admin can view reports of the model.
     */
    public function reports(Admin $admin)
    {
        return $admin->canAny(['members:reports-daily', 'members:reports-monthly', 'members:reports-yearly']);
    }

    /**
     * Determine whether the admin can view daily reports of the model.
     */
    public function reportsDaily(Admin $admin)
    {
        return $admin->can('members:reports-daily');
    }

    /**
     * Determine whether the admin can view monthly reports of the model.
     */
    public function reportsMonthly(Admin $admin)
    {
        return $admin->can('members:reports-monthly');
    }

    /**
     * Determine whether the admin can view yearly reports of the model.
     */
    public function reportsYearly(Admin $admin)
    {
        return $admin->can('members:reports-yearly');
    }

    /**
     * Determine whether the admin can view enquiry of the model.
     */
    public function enquiry(Admin $admin)
    {
        return $admin->can('members:enquiry');
    }
}
