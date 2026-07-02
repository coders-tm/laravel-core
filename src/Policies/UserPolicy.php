<?php

namespace Coderstm\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model as Admin;

class UserPolicy
{
    use HandlesAuthorization;

    public function before(Admin $admin, $ability)
    {
        if ($admin->is_supper_admin) {
            return true;
        }
    }

    public function viewAny(Admin $admin)
    {
        return $admin->can('members:list');
    }

    public function view(Admin $admin, $user)
    {
        if (is_user()) {
            return $user->id == user()->id;
        }

        return $admin->can('members:view');
    }

    public function create(Admin $admin)
    {
        return $admin->can('members:new');
    }

    public function update(Admin $admin)
    {
        return $admin->can('members:edit');
    }

    public function delete(Admin $admin)
    {
        return $admin->can('members:delete');
    }

    public function restore(Admin $admin)
    {
        return $admin->can('members:restore');
    }

    public function forceDelete(Admin $admin)
    {
        return $admin->can('members:forceDelete');
    }
}
