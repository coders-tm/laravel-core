<?php

namespace Coderstm\Http\Controllers;

use Coderstm\Coderstm;
use Coderstm\Facades\Currency;
use Coderstm\Models\Subscription\Feature;
use Coderstm\Models\Subscription\Plan;
use Coderstm\Traits\HasResourceActions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Gate;

class PlanController extends Controller
{
    use HasResourceActions;

    public function __construct()
    {
        $this->useModel(Coderstm::$planModel);
        $this->authorizeResource(Coderstm::$planModel, 'plan', [
            'except' => ['show'],
        ]);
    }

    public function index(Request $request)
    {
        $plan = Coderstm::$planModel::query();

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

    /**
     * Store a plan.
     *
     * @param  mixed  $plan
     * @return JsonResponse
     */
    public function store(Request $request, $plan)
    {
        $rules = [
            'label' => 'required',
            'interval' => 'required',
            'interval_count' => 'required',
            'price' => 'required',
        ];

        // Validate those rules
        $request->validate($rules);

        // create the plan
        $plan = Coderstm::$planModel::create($request->input());

        if ($request->filled('features')) {
            $plan->syncFeatures($request->features);
        }

        return response()->json([
            'data' => $this->toArray($plan->fresh('features')),
            'message' => __('Plan has been created successfully!'),
        ], 200);
    }

    public function show($plan)
    {
        $plan = Coderstm::$planModel::withTrashed()->findOrFail($plan);

        Gate::authorize('view', $plan);

        return response()->json($this->toArray($plan->load('features')), 200);
    }

    /**
     * Update a plan.
     *
     * @param  mixed  $plan
     * @return JsonResponse
     */
    public function update(Request $request, $plan)
    {
        $rules = [
            'label' => 'required',
            'interval' => 'required',
            'interval_count' => 'required',
            'price' => 'required',
        ];

        // Validate those rules
        $request->validate($rules);

        // update the plan
        $plan->update($request->input());

        if ($request->filled('features')) {
            $plan->syncFeatures($request->features);
        }

        return response()->json([
            'data' => $this->toArray($plan->fresh('features')),
            'message' => __('Plan has been updated successfully!'),
        ], 200);
    }

    /**
     * Destroy a plan.
     *
     * @param  mixed  $plan
     * @return JsonResponse
     */
    public function destroy(Request $request, $plan)
    {
        $this->authorize('delete', $plan);

        if ($plan->subscriptions()->count() > 0) {
            return response()->json([
                'message' => __('Cannot delete plan with active subscriptions.'),
            ], 422);
        }

        if ($request->boolean('force')) {
            $plan->forceDelete();
        } else {
            $plan->delete();
        }

        return response()->json([
            'message' => __('Plan deleted successfully!'),
        ], 200);
    }

    /**
     * Change plan active status.
     *
     * @param  mixed  $plan
     * @return JsonResponse
     */
    public function changeActive(Request $request, $plan)
    {
        $plan->update([
            'is_active' => ! $plan->is_active,
        ]);

        $type = $plan->active ? 'active' : 'deactive';

        return response()->json([
            'message' => __('Plan marked as :type successfully!', ['type' => __($type)]),
        ], 200);
    }

    public function shared(Request $request)
    {
        $plans = Coderstm::$planModel::onlyActive();

        if ($request->filled('plan_id')) {
            $plans->orWhere('id', $request->plan_id);
        }

        $plans = $plans->get();

        // Convert prices to user currency automatically using Currencyable interface
        $plans = Currency::transform($plans);

        return response()->json($plans, 200);
    }

    public function features(Request $request)
    {
        return response()->json(Feature::all(), 200);
    }

    /**
     * Convert plan to array.
     *
     * @param  mixed  $plan
     * @return array
     */
    private function toArray($plan)
    {
        return array_merge($plan->toArray(), [
            'features' => $plan->features->mapWithKeys(function ($item) {
                if ($item->isBoolean()) {
                    return [$item->slug => (bool) $item->pivot->value];
                }

                return [$item->slug => $item->pivot->value];
            })->toArray(),
        ]);
    }
}
