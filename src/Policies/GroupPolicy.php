<?php

namespace Coderstm\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

class GroupPolicy
{
    use HandlesAuthorization;

    public function before(Model $admin, string $ability)
    {
        if ($admin->is_supper_admin) {
            return true;
        }
    }

    public function viewAny(Model $admin): bool
    {
        return $admin->can('groups:list');
    }

    public function view(Model $admin): bool
    {
        return $admin->can('groups:view');
    }

    public function create(Model $admin): bool
    {
        return $admin->can('groups:new');
    }

    public function update(Model $admin): bool
    {
        return $admin->can('groups:edit');
    }

    public function delete(Model $admin): bool
    {
        return $admin->can('groups:delete');
    }

    public function restore(Model $admin): bool
    {
        return $admin->can('groups:restore');
    }

    public function forceDelete(Model $admin): bool
    {
        return $admin->can('groups:forceDelete');
    }
}
