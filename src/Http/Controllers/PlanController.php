<?php

namespace Coderstm\Http\Controllers;

use Coderstm\Models\Plan;
use Illuminate\Http\Request;
use Coderstm\Http\Controllers\Controller;
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
            'monthly_fee' => 'required',
            'yearly_fee' => 'required',
        ];

        // Validate those rules
        $this->validate($request, $rules);

        // create the plan
        $plan = Plan::create($request->input());

        if ($request->filled('features')) {
            $plan->syncFeatures(collect($request->features));
        }

        return response()->json([
            'data' => $plan->fresh(['prices', 'features']),
            'message' => trans('coderstm::messages.plans.store'),
        ], 200);
    }

    public function show(Plan $plan)
    {
        return response()->json($plan->load('features'), 200);
    }

    public function update(Request $request, Plan $plan)
    {

        $rules = [
            'label' => 'required',
        ];

        // Validate those rules
        $this->validate($request, $rules);

        // update the plan
        $plan->update($request->input());

        if ($request->filled('features')) {
            $plan->syncFeatures(collect($request->features));
        }

        return response()->json([
            'data' => $plan->fresh([
                'features'
            ]),
            'message' => trans('coderstm::messages.plans.updated'),
        ], 200);
    }

    public function destroy(Plan $plan)
    {
        $plan->delete();
        return response()->json([
            'message' => trans_choice('coderstm::messages.plans.destroy', 1),
        ], 200);
    }

    public function destroySelected(Request $request, Plan $plan)
    {
        $this->validate($request, [
            'items' => 'required',
        ]);
        $plan->whereIn('id', $request->items)->each(function ($item) {
            $item->delete();
        });
        return response()->json([
            'message' => trans_choice('coderstm::messages.plans.destroy', 2),
        ], 200);
    }

    public function restore($id)
    {
        Plan::onlyTrashed()
            ->where('id', $id)->each(function ($item) {
                $item->restore();
            });
        return response()->json([
            'message' => trans_choice('coderstm::messages.plans.restored', 1),
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
            'message' => trans_choice('coderstm::messages.plans.restored', 2),
        ], 200);
    }

    public function changeActive(Request $request, Plan $plan)
    {
        $plan->update([
            'is_active' => !$plan->is_active
        ]);

        $type = $plan->is_active ? 'active' : 'deactive';
        return response()->json([
            'message' => trans('coderstm::messages.plans.status', ['type' => trans('coderstm::messages.attributes.' . $type)]),
        ], 200);
    }
}
