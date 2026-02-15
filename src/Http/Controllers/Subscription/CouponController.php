<?php

namespace Coderstm\Http\Controllers\Subscription;

use Coderstm\Http\Controllers\Controller;
use Coderstm\Http\Resources\Coupon\PlanCollection;
use Coderstm\Http\Resources\Coupon\ProductCollection;
use Coderstm\Http\Resources\CouponResource;
use Coderstm\Models\Coupon;
use Coderstm\Models\Shop\Product;
use Coderstm\Models\Subscription\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Gate;

class CouponController extends Controller
{
    use \Coderstm\Traits\HasResourceActions;

    public function __construct()
    {
        $this->useModel(Coupon::class);
        $this->authorizeResource(Coupon::class, 'coupon', ['except' => ['show']]);
    }

    public function index(Request $request)
    {
        $coupon = Coupon::query();
        if ($request->filled('filter')) {
            $coupon->where(function ($query) use ($request) {
                $query->where('name', 'like', "%{$request->filter}%")->orWhere('promotion_code', 'like', "%{$request->filter}%");
            });
        }
        if ($request->boolean('active')) {
            $coupon->onlyActive();
        }
        if ($request->boolean('deleted')) {
            $coupon->onlyTrashed();
        }
        $coupon = $coupon->orderBy($request->sortBy ?? 'created_at', $request->direction ?? 'desc')->paginate($request->rowsPerPage ?? 15);

        return new ResourceCollection($coupon);
    }

    public function store(Request $request)
    {
        $rules = ['name' => 'required', 'promotion_code' => 'required|unique:coupons,promotion_code', 'duration' => 'required', 'duration_in_months' => 'required_if:duration,repeating', 'amount_off' => 'required_if:percent_off,null', 'percent_off' => 'required_if:amount_off,null'];
        $request->validate($rules);
        $coupon = Coupon::create($request->input());
        $coupon = $coupon->syncPlans($request->plans ?? []);

        return response()->json(['data' => $coupon->fresh(['plans', 'logs']), 'message' => __('Coupon has been created successfully!')], 200);
    }

    public function show($coupon)
    {
        $coupon = Coupon::withTrashed()->findOrFail($coupon);
        Gate::authorize('view', $coupon);

        return response()->json(new CouponResource($coupon->load('plans', 'logs')), 200);
    }

    public function update(Request $request, Coupon $coupon)
    {
        $rules = ['name' => 'required', 'promotion_code' => 'required|unique:coupons,promotion_code,'.$coupon->id, 'duration' => 'required', 'duration_in_months' => 'required_if:duration,repeating', 'amount_off' => 'required_if:percent_off,null', 'percent_off' => 'required_if:amount_off,null'];
        $request->validate($rules);
        $coupon->update($request->input());
        $coupon = $coupon->syncPlans($request->plans ?? []);

        return response()->json(['data' => $coupon->fresh(['plans', 'logs']), 'message' => __('Coupon has been updated successfully!')], 200);
    }

    public function changeActive(Request $request, Coupon $coupon)
    {
        $coupon->update(['active' => ! $coupon->active]);
        $type = $coupon->active ? 'active' : 'deactive';

        return response()->json(['message' => __('Coupon marked as :type successfully!', ['type' => __($type)])], 200);
    }

    public function logs(Request $request, Coupon $coupon)
    {
        $request->validate(['message' => 'required']);
        $note = $coupon->logs()->create($request->input());

        return response()->json(['data' => $note->load('admin'), 'message' => __('New log has been created.')], 200);
    }

    public function products(Request $request)
    {
        $query = Product::query();
        if ($request->filled('filter')) {
            $query->where('title', 'like', "%{$request->filter}%");
        }
        $plans = $query->orderBy($request->sortBy ?? 'created_at', $request->direction ?? 'asc')->paginate($request->rowsPerPage ?? 15);

        return new ProductCollection($plans);
    }

    public function plans(Request $request)
    {
        $query = Plan::with('product')->where('is_active', true);
        if ($request->filled('filter')) {
            $query->where(function ($q) use ($request) {
                $q->where('label', 'like', "%{$request->filter}%")->orWhereHas('product', function ($productQuery) use ($request) {
                    $productQuery->where('title', 'like', "%{$request->filter}%");
                });
            });
        }
        $plans = $query->orderBy($request->sortBy ?? 'created_at', $request->direction ?? 'asc')->paginate($request->rowsPerPage ?? 15);

        return new PlanCollection($plans);
    }
}
