<?php

namespace Coderstm\Http\Controllers;

use Coderstm\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Gate;

class GroupController extends Controller
{
    use \Coderstm\Traits\HasResourceActions;

    public function __construct()
    {
        $this->useModel(Group::class);
        $this->authorizeResource(Group::class, 'group', ['except' => ['show']]);
    }

    public function index(Request $request)
    {
        $group = Group::query();
        if ($request->has('filter') && ! empty($request->filter)) {
            $group->where('name', 'like', "%{$request->filter}%");
        }
        if ($request->input('deleted') ? $request->boolean('deleted') : false) {
            $group->onlyTrashed();
        }
        $group = $group->orderBy($request->sortBy ?? 'created_at', $request->direction ?? 'desc')->paginate($request->rowsPerPage ?? 15);

        return new ResourceCollection($group);
    }

    public function store(Request $request)
    {
        $rules = ['name' => 'required|unique:groups'];
        $request->validate($rules);
        $group = Group::create($request->input());
        $permissions = collect($request->permissions)->filter(function ($permission) {
            return ! is_null($permission['access']);
        })->mapWithKeys(function ($permission) {
            return [$permission['id'] => ['access' => $permission['access']]];
        });
        $group->permissions()->sync($permissions);

        return response()->json(['data' => $group->load('permissions'), 'message' => __('Group has been created successfully!')], 200);
    }

    public function show($group)
    {
        $group = Group::withTrashed()->findOrFail($group);
        Gate::authorize('view', $group);

        return response()->json($this->toArray($group), 200);
    }

    public function update(Request $request, Group $group)
    {
        $rules = ['name' => 'required|unique:groups,name,'.$group->id];
        $request->validate($rules);
        $group->update($request->input());
        $permissions = collect($request->permissions)->filter(function ($permission) {
            return ! is_null($permission['access']);
        })->mapWithKeys(function ($permission) {
            return [$permission['id'] => ['access' => $permission['access']]];
        });
        $group->permissions()->sync($permissions);

        return response()->json(['data' => $group->load('permissions'), 'message' => __('Group has been updated successfully!')], 200);
    }

    private function toArray(Group $group)
    {
        $data = $group->toArray();
        $data['permissions'] = $group->permissions->map(function ($permission) {
            return ['id' => $permission->id, 'access' => $permission->pivot->access];
        });

        return $data;
    }
}
