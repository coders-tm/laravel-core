<?php

namespace Coderstm\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model as Enquiry;
use Illuminate\Database\Eloquent\Model as User;

class EnquiryPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     */
    public function before(User $admin, $ability)
    {
        if ($admin->is_supper_admin) {
            return true;
        }
    }

    /**
     * Determine whether the admin can view any models.
     */
    public function viewAny(User $admin)
    {
        if (is_user()) {
            return true;
        }

        return $admin->canAny(['tickets:read', 'tickets:write', 'tickets:editor']);
    }

    /**
     * Determine whether the admin can view the model.
     */
    public function view(User $admin, Enquiry $enquiry)
    {
        if (is_user()) {
            return $enquiry->email == user()->email;
        }

        return $admin->canAny(['tickets:read', 'tickets:write', 'tickets:editor']);
    }

    /**
     * Determine whether the admin can create models.
     */
    public function create(User $admin)
    {
        if (is_user()) {
            return true;
        }

        return $admin->canAny(['tickets:write', 'tickets:editor']);
    }

    /**
     * Determine whether the admin can update the model.
     */
    public function update(User $admin, Enquiry $enquiry)
    {
        if (is_user()) {
            return $enquiry->email == user()->email;
        }

        return $admin->canAny(['tickets:write', 'tickets:editor']);
    }

    /**
     * Determine whether the admin can delete the model.
     */
    public function delete(User $admin)
    {
        return $admin->can('tickets:write');
    }

    /**
     * Determine whether the admin can restore the model.
     */
    public function restore(User $admin)
    {
        return $admin->can('tickets:write');
    }

    /**
     * Determine whether the admin can permanently delete the model.
     */
    public function forceDelete(User $admin)
    {
        return $admin->can('tickets:write');
    }
}
