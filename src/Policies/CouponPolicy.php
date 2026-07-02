<?php

namespace Coderstm\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;

class CouponPolicy
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
        return $admin->can('coupons:list');
    }

    public function view($admin, $coupon): bool
    {
        return $admin->can('coupons:view');
    }

    public function create($admin): bool
    {
        return $admin->can('coupons:new');
    }

    public function update($admin, $coupon): bool
    {
        return $admin->can('coupons:edit');
    }

    public function delete($admin): bool
    {
        return $admin->can('coupons:delete');
    }

    public function restore($admin): bool
    {
        return $admin->can('coupons:restore');
    }

    public function forceDelete($admin): bool
    {
        return $admin->can('coupons:forceDelete');
    }
}
