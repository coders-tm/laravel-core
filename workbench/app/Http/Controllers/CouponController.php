<?php

namespace App\Http\Controllers;

use Coderstm\Models\Coupon;
use Illuminate\Http\Request;
use Coderstm\Models\Shop\Product;
use Coderstm\Models\Subscription\Plan;
use Coderstm\Http\Resources\CouponResource;
use Coderstm\Http\Resources\Coupon\PlanCollection;
use Coderstm\Http\Resources\Coupon\ProductCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Coderstm\Http\Controllers\Subscription\CouponController as BaseCouponController;

class CouponController extends BaseCouponController
{
    public function index(Request $request, Coupon $coupon)
    {
        $coupon = $coupon->with(['plans', 'products']);

        if ($request->filled('filter')) {
            $coupon->where(function ($query) use ($request) {
                $query->where('name', 'like', "%{$request->filter}%")
                    ->orWhere('promotion_code', 'like', "%{$request->filter}%");
            });
        }

        if ($request->boolean('active')) {
            $coupon->onlyActive();
        }

        if ($request->boolean('deleted')) {
            $coupon->onlyTrashed();
        }

        $coupon = $coupon->orderBy($request->sortBy ?? 'created_at', $request->direction ?? 'desc')
            ->paginate($request->rowsPerPage ?? 15);
        return new ResourceCollection($coupon);
    }

    public function store(Request $request, Coupon $coupon)
    {
        $rules = [
            'type' => 'required',
            'name' => 'required',
            'promotion_code' => 'required|unique:coupons,promotion_code',
            'duration' => 'required',
            'duration_in_months' => 'required_if:duration,repeating',
            'discount_type' => 'required|in:percentage,fixed,override',
            'value' => 'required|numeric|min:0',
            'products' => 'sometimes|array',
            'plans' => 'sometimes|array',
        ];

        // Validate those rules
        $this->validate($request, $rules);

        // create the coupon
        $coupon = Coupon::create($request->input());

        // Sync relationships
        if ($request->has('plans')) {
            $coupon->syncPlans($request->plans);
        }

        if ($request->has('products')) {
            $coupon->syncProducts($request->products);
        }

        return response()->json([
            'data' => new CouponResource($coupon->fresh(['plans', 'products', 'logs'])),
            'message' => trans('messages.coupons.store'),
        ], 200);
    }

    public function update(Request $request, Coupon $coupon)
    {
        $rules = [
            'name' => 'required',
            'promotion_code' => 'required|unique:coupons,promotion_code,' . $coupon->id,
            'value' => 'required|numeric|min:0',
            'products' => 'sometimes|array',
            'plans' => 'sometimes|array',
        ];

        // Validate those rules
        $this->validate($request, $rules);

        // update the coupon
        $coupon->update($request->only([
            'name',
            'promotion_code',
            'active',
            'auto_apply',
        ]));

        // Sync relationships
        if ($request->has('plans')) {
            $coupon->syncPlans($request->plans);
        }

        if ($request->has('products')) {
            $coupon->syncProducts($request->products);
        }

        return response()->json([
            'data' => new CouponResource($coupon->fresh(['plans', 'products', 'logs'])),
            'message' => trans('messages.coupons.updated'),
        ], 200);
    }

    /**
     * Get products for select options
     */
    public function products(Request $request)
    {
        $query = Product::query();

        if ($request->filled('filter')) {
            $query->where('title', 'like', "%{$request->filter}%");
        }

        $plans = $query->orderBy($request->sortBy ?? 'created_at', $request->direction ?? 'asc')
            ->paginate($request->rowsPerPage ?? 15);

        return new ProductCollection($plans);
    }

    /**
     * Get plans for select options (includes product information)
     */
    public function plans(Request $request)
    {
        $query = Plan::with('product')->where('is_active', true);

        if ($request->filled('filter')) {
            $query->where(function ($q) use ($request) {
                $q->where('label', 'like', "%{$request->filter}%")
                    ->orWhereHas('product', function ($productQuery) use ($request) {
                        $productQuery->where('title', 'like', "%{$request->filter}%");
                    });
            });
        }

        $plans = $query->orderBy($request->sortBy ?? 'created_at', $request->direction ?? 'asc')
            ->paginate($request->rowsPerPage ?? 15);

        return new PlanCollection($plans);
    }
}
