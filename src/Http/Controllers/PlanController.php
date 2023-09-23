<?php

namespace Coderstm\Http\Controllers;

use Coderstm\Models\Plan;
use Illuminate\Http\Request;
use Coderstm\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PlanController extends Controller
{
    /**
     * Create the controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->authorizeResource(Plan::class);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
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

        $plan = $plan->orderBy(optional($request)->sortBy ?? 'created_at', optional($request)->direction ?? 'desc')
            ->paginate(optional($request)->rowsPerPage ?? 15);
        return new ResourceCollection($plan);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
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

    /**
     * Display the specified resource.
     *
     * @param  \Coderstm\Models\Plan  $plan
     * @return \Illuminate\Http\Response
     */
    public function show(Plan $plan)
    {
        return response()->json($plan->load('features'), 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Coderstm\Models\Plan  $plan
     * @return \Illuminate\Http\Response
     */
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

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Coderstm\Models\Plan  $plan
     * @return \Illuminate\Http\Response
     */
    public function destroy(Plan $plan)
    {
        $plan->delete();
        return response()->json([
            'message' => trans_choice('coderstm::messages.plans.destroy', 1),
        ], 200);
    }

    /**
     * Remove the selected resource from storage.
     *
     * @param  \Coderstm\Models\Plan  $plan
     * @return \Illuminate\Http\Response
     */
    public function destroy_selected(Request $request, Plan $plan)
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

    /**
     * Restore the specified resource from storage.
     *
     * @param  \Coderstm\Models\Plan  $plan
     * @return \Illuminate\Http\Response
     */
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

    /**
     * Remove the selected resource from storage.
     *
     * @param  \Coderstm\Models\Plan  $plan
     * @return \Illuminate\Http\Response
     */
    public function restore_selected(Request $request, Plan $plan)
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

    /**
     * Change active of specified resource from storage.
     *
     * @param  \Coderstm\Models\Plan  $plan
     * @return \Illuminate\Http\Response
     */
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
