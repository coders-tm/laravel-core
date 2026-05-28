<?php

namespace Coderstm\Policies;

use Coderstm\Models\Coupon;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

class CouponPolicy
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
        return $admin->can('coupons:list');
    }

    public function view(Model $admin, Coupon $coupon): bool
    {
        return $admin->can('coupons:view');
    }

    public function create(Model $admin): bool
    {
        return $admin->can('coupons:new');
    }

    public function update(Model $admin, Coupon $coupon): bool
    {
        return $admin->can('coupons:edit');
    }

    public function delete(Model $admin): bool
    {
        return $admin->can('coupons:delete');
    }

    public function restore(Model $admin): bool
    {
        return $admin->can('coupons:restore');
    }

    public function forceDelete(Model $admin): bool
    {
        return $admin->can('coupons:forceDelete');
    }
}
