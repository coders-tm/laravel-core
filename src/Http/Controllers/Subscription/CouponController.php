<?php

namespace Coderstm\Http\Controllers\Subscription;

use Coderstm\Models\Coupon;
use Illuminate\Http\Request;
use Coderstm\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CouponController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Coupon::class);
    }

    public function index(Request $request, Coupon $coupon)
    {
        $coupon = $coupon->query();

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
            'name' => 'required',
            'promotion_code' => 'required|unique:coupons,promotion_code',
            'duration' => 'required',
            'duration_in_months' => 'required_if:duration,repeating',
            'amount_off' => 'required_if:percent_off,null',
            'percent_off' => 'required_if:amount_off,null',
        ];

        // Validate those rules
        $this->validate($request, $rules);

        // create the coupon
        $coupon = Coupon::create($request->input());

        $coupon = $coupon->syncPlans($request->plans ?? []);

        return response()->json([
            'data' => $coupon->fresh(['plans', 'logs']),
            'message' => trans('messages.coupons.store'),
        ], 200);
    }

    public function show(Coupon $coupon)
    {
        return response()->json($coupon->load('plans', 'logs'), 200);
    }

    public function update(Request $request, Coupon $coupon)
    {
        $rules = [
            'name' => 'required',
            'promotion_code' => 'required|unique:coupons,promotion_code,' . $coupon->id,
            'duration' => 'required',
            'duration_in_months' => 'required_if:duration,repeating',
            'amount_off' => 'required_if:percent_off,null',
            'percent_off' => 'required_if:amount_off,null',
        ];

        // Validate those rules
        $this->validate($request, $rules);

        // update the coupon
        $coupon->update($request->input());

        $coupon = $coupon->syncPlans($request->plans ?? []);

        return response()->json([
            'data' => $coupon->fresh(['plans', 'logs']),
            'message' => trans('messages.coupons.updated'),
        ], 200);
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return response()->json([
            'message' => trans_choice('messages.coupons.destroy', 1),
        ], 200);
    }

    public function destroySelected(Request $request, Coupon $coupon)
    {
        $this->validate($request, [
            'items' => 'required',
        ]);
        $coupon->whereIn('id', $request->items)->each(function ($item) {
            $item->delete();
        });
        return response()->json([
            'message' => trans_choice('messages.coupons.destroy', 2),
        ], 200);
    }

    public function restore($id)
    {
        Coupon::onlyTrashed()
            ->where('id', $id)->each(function ($item) {
                $item->restore();
            });
        return response()->json([
            'message' => trans_choice('messages.coupons.restored', 1),
        ], 200);
    }

    public function restoreSelected(Request $request, Coupon $coupon)
    {
        $this->validate($request, [
            'items' => 'required',
        ]);
        $coupon->onlyTrashed()
            ->whereIn('id', $request->items)->each(function ($item) {
                $item->restore();
            });
        return response()->json([
            'message' => trans_choice('messages.coupons.restored', 2),
        ], 200);
    }

    public function changeActive(Request $request, Coupon $coupon)
    {
        $coupon->update([
            'active' => !$coupon->active
        ]);

        $type = $coupon->active ? 'active' : 'deactive';
        return response()->json([
            'message' => trans('messages.coupons.status', ['type' => trans('messages.attributes.' . $type)]),
        ], 200);
    }

    /**
     * Create logs for specified resource from storage.
     */
    public function logs(Request $request, Coupon $coupon)
    {
        $this->validate($request, [
            'message' => 'required',
        ]);

        $note = $coupon->logs()->create($request->input());

        return response()->json([
            'data' => $note->load('admin'),
            'message' => __('New log has been created.'),
        ], 200);
    }
}
