<?php

namespace Coderstm\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model as Admin;

class AdminPolicy
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
        return $admin->can('staff:list');
    }

    public function view(Admin $admin)
    {
        return $admin->can('staff:view');
    }

    public function create(Admin $admin)
    {
        return $admin->can('staff:new');
    }

    public function update(Admin $admin)
    {
        return $admin->can('staff:edit');
    }

    public function delete(Admin $admin)
    {
        return $admin->can('staff:delete');
    }

    public function restore(Admin $admin)
    {
        return $admin->can('staff:restore');
    }

    public function forceDelete(Admin $admin)
    {
        return $admin->can('staff:forceDelete');
    }
}
