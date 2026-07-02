<?php

namespace Coderstm\Policies\Subscription;

use Illuminate\Auth\Access\HandlesAuthorization;

class PlanPolicy
{
    use HandlesAuthorization;

    public function before($admin, string $ability)
    {
        if ($admin->is_supper_admin) {
            return true;
        }
    }

    public function viewAny($admin): bool
    {
        return $admin->can('plans:list');
    }

    public function view($admin, $plan): bool
    {
        return $admin->can('plans:view');
    }

    public function create($admin): bool
    {
        return $admin->can('plans:new');
    }

    public function update($admin, $plan): bool
    {
        return $admin->can('plans:edit') && ($plan->user_id == $admin->id || $plan->hasUser($admin->id));
    }

    public function delete($admin): bool
    {
        return $admin->can('plans:delete');
    }

    public function restore($admin): bool
    {
        return $admin->can('plans:restore');
    }

    public function forceDelete($admin): bool
    {
        return $admin->can('plans:forceDelete');
    }
}
