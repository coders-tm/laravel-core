<?php

namespace App\Policies;

use App\Models\Admin;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     *
     * @param mixed $admin
     * @param mixed $ability
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
     * @param mixed $admin
     * @return bool|mixed
     */
    public function viewAny($admin)
    {
        return $admin->can('members:list');
    }

    /**
     * Determine whether the admin can view the model.
     *
     * @param mixed $admin
     * @param mixed $user
     * @return bool|mixed
     */
    public function view($admin, $user)
    {
        if (is_user()) {
            return $user->id == user()->id;
        }

        return $admin->can('members:view');
    }

    /**
     * Determine whether the admin can create models.
     *
     * @param mixed $admin
     * @return bool|mixed
     */
    public function create($admin)
    {
        return $admin->can('members:new');
    }

    /**
     * Determine whether the admin can update the model.
     *
     * @param mixed $admin
     * @return bool|mixed
     */
    public function update($admin)
    {
        return $admin->can('members:edit');
    }

    /**
     * Determine whether the admin can delete the model.
     *
     * @param mixed $admin
     * @return bool|mixed
     */
    public function delete($admin)
    {
        return $admin->can('members:delete');
    }

    /**
     * Determine whether the admin can restore the model.
     *
     * @param mixed $admin
     * @return bool|mixed
     */
    public function restore($admin)
    {
        return $admin->can('members:restore');
    }

    /**
     * Determine whether the admin can permanently delete the model.
     *
     * @param mixed $admin
     * @return bool|mixed
     */
    public function forceDelete($admin)
    {
        return $admin->can('members:forceDelete');
    }

    /**
     * Determine whether the admin can view reports of the model.
     *
     * @param mixed $admin
     * @return bool|mixed
     */
    public function reports($admin)
    {
        return $admin->canAny(['members:reports-daily', 'members:reports-monthly', 'members:reports-yearly']);
    }

    /**
     * Determine whether the admin can view daily reports of the model.
     *
     * @param mixed $admin
     * @return bool|mixed
     */
    public function reportsDaily($admin)
    {
        return $admin->can('members:reports-daily');
    }

    /**
     * Determine whether the admin can view monthly reports of the model.
     *
     * @param mixed $admin
     * @return bool|mixed
     */
    public function reportsMonthly($admin)
    {
        return $admin->can('members:reports-monthly');
    }

    /**
     * Determine whether the admin can view yearly reports of the model.
     *
     * @param mixed $admin
     * @return bool|mixed
     */
    public function reportsYearly($admin)
    {
        return $admin->can('members:reports-yearly');
    }

    /**
     * Determine whether the admin can view enquiry of the model.
     *
     * @param mixed $admin
     * @return bool|mixed
     */
    public function enquiry($admin)
    {
        return $admin->can('members:enquiry');
    }
}
