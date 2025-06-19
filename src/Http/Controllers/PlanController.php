<?php

namespace Coderstm\Http\Controllers;

use Illuminate\Http\Request;
use Coderstm\Models\Subscription\Plan;
use Coderstm\Rules\SubscriptionExists;
use Coderstm\Http\Controllers\Controller;
use Coderstm\Models\Subscription\Feature;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PlanController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Plan::class);
    }

    public function index(Request $request, Plan $plan)
    {
        $plan = $plan->query();

        if ($request->filled('filter')) {
            $plan->where('label', 'like', "%{$request->filter}%");
        }

        if ($request->boolean('active')) {
            $plan->onlyActive();
        }

        if ($request->filled('plan_id')) {
            $plan->orWhere('id', $request->plan_id);
        }

        if ($request->boolean('deleted')) {
            $plan->onlyTrashed();
        }

        $plan = $plan->orderBy($request->sortBy ?? 'created_at', $request->direction ?? 'desc')
            ->paginate($request->rowsPerPage ?? 15);
        return new ResourceCollection($plan);
    }

    public function store(Request $request, Plan $plan)
    {
        $rules = [
            'label' => 'required',
            'interval' => 'required',
            'interval_count' => 'required',
            'price' => 'required',
        ];

        // Validate those rules
        $this->validate($request, $rules);

        // create the plan
        $plan = Plan::create($request->input());

        if ($request->filled('features')) {
            $plan->syncFeatures($request->features);
        }

        return response()->json([
            'data' => $this->toArray($plan->load('features')),
            'message' => trans('messages.plans.store'),
        ], 200);
    }

    public function show(Plan $plan)
    {
        return response()->json($this->toArray($plan->load('features')), 200);
    }

    public function update(Request $request, Plan $plan)
    {

        $rules = [
            'label' => 'required',
            'interval' => 'required',
            'interval_count' => 'required',
            'price' => 'required',
        ];

        // Validate those rules
        $this->validate($request, $rules);

        // update the plan
        $plan->update($request->input());

        if ($request->filled('features')) {
            $plan->syncFeatures($request->features);
        }

        return response()->json([
            'data' => $this->toArray($plan->fresh([
                'features'
            ])),
            'message' => trans('messages.plans.updated'),
        ], 200);
    }

    public function destroy(Plan $plan)
    {
        if ($plan->subscriptions()->count() > 0) {
            abort(422, 'The plan cannot be deleted because it has active subscriptions.');
        }

        $plan->delete();
        return response()->json([
            'message' => trans_choice('messages.plans.destroy', 1),
        ], 200);
    }

    public function destroySelected(Request $request, Plan $plan)
    {
        $this->validate($request, [
            'items' => 'required|array',
            'items.*' => ['exists:plans,id', new SubscriptionExists],
        ]);

        $plan->whereIn('id', $request->items)->each(function ($item) {
            $item->delete();
        });

        return response()->json([
            'message' => trans_choice('messages.plans.destroy', 2),
        ], 200);
    }

    public function restore($id)
    {
        Plan::onlyTrashed()
            ->where('id', $id)->each(function ($item) {
                $item->restore();
            });
        return response()->json([
            'message' => trans_choice('messages.plans.restored', 1),
        ], 200);
    }

    public function restoreSelected(Request $request, Plan $plan)
    {
        $this->validate($request, [
            'items' => 'required',
        ]);
        $plan->onlyTrashed()
            ->whereIn('id', $request->items)->each(function ($item) {
                $item->restore();
            });
        return response()->json([
            'message' => trans_choice('messages.plans.restored', 2),
        ], 200);
    }

    public function changeActive(Request $request, Plan $plan)
    {
        $plan->update([
            'is_active' => !$plan->is_active
        ]);

        $type = $plan->is_active ? 'active' : 'deactive';
        return response()->json([
            'message' => trans('messages.plans.status', ['type' => trans('messages.attributes.' . $type)]),
        ], 200);
    }

    public function shared(Request $request)
    {
        $plan = Plan::onlyActive();

        if ($request->filled('plan_id')) {
            $plan->orWhere('id', $request->plan_id);
        }

        return response()->json($plan->get(), 200);
    }

    public function features(Request $request)
    {
        return response()->json(Feature::all(), 200);
    }

    private function toArray(Plan $plan)
    {
        return array_merge($plan->toArray(), [
            'features' => $plan->features->mapWithKeys(function ($item) {
                if ($item->isBoolean()) {
                    return [$item->slug => (bool) $item->pivot->value];
                }
                return [$item->slug => $item->pivot->value];
            })->toArray()
        ]);
    }
}
