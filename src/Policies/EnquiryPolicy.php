<?php

namespace Coderstm\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model as Enquiry;
use Illuminate\Database\Eloquent\Model as User;

class EnquiryPolicy
{
    use HandlesAuthorization;

    public function before(User $admin, $ability)
    {
        if ($admin->is_supper_admin) {
            return true;
        }
    }

    public function viewAny(User $admin)
    {
        if (is_user()) {
            return true;
        }

        return $admin->can('tickets:list');
    }

    public function view(User $admin, Enquiry $enquiry)
    {
        if (is_user()) {
            return $enquiry->email == user()->email;
        }

        return $admin->can('tickets:view');
    }

    public function create(User $admin)
    {
        if (is_user()) {
            return true;
        }

        return $admin->can('tickets:new');
    }

    public function update(User $admin, Enquiry $enquiry)
    {
        if (is_user()) {
            return $enquiry->email == user()->email;
        }

        return $admin->can('tickets:edit');
    }

    public function delete(User $admin)
    {
        return $admin->can('tickets:delete');
    }

    public function restore(User $admin)
    {
        return $admin->can('tickets:restore');
    }

    public function forceDelete(User $admin)
    {
        return $admin->can('tickets:forceDelete');
    }
}
