<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Enquiry;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EnquiryPolicy
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
        if (is_user()) {
            return true;
        }

        return $admin->can('tickets:list');
    }

    /**
     * Determine whether the admin can view the model.
     *
     * @param mixed $admin
     * @param mixed $enquiry
     * @return bool|mixed
     */
    public function view($admin, $enquiry)
    {
        if (is_user()) {
            return $enquiry->email == user()->email;
        }

        return $admin->can('tickets:view');
    }

    /**
     * Determine whether the admin can create models.
     *
     * @param mixed $admin
     * @return bool|mixed
     */
    public function create($admin)
    {
        if (is_user()) {
            return true;
        }

        return $admin->can('tickets:new');
    }

    /**
     * Determine whether the admin can update the model.
     *
     * @param mixed $admin
     * @param mixed $enquiry
     * @return bool|mixed
     */
    public function update($admin, $enquiry)
    {
        if (is_user()) {
            return $enquiry->email == user()->email;
        }

        return $admin->can('tickets:edit');
    }

    /**
     * Determine whether the admin can delete the model.
     *
     * @param mixed $admin
     * @return bool|mixed
     */
    public function delete($admin)
    {
        return $admin->can('tickets:delete');
    }

    /**
     * Determine whether the admin can restore the model.
     *
     * @param mixed $admin
     * @return bool|mixed
     */
    public function restore($admin)
    {
        return $admin->can('tickets:restore');
    }

    /**
     * Determine whether the admin can permanently delete the model.
     *
     * @param mixed $admin
     * @return bool|mixed
     */
    public function forceDelete($admin)
    {
        return $admin->can('tickets:forceDelete');
    }
}
