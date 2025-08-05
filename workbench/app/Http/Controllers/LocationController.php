<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Coderstm\Models\Shop\Location;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\ResourceCollection;

class LocationController extends Controller
{
    public function index(Request $request, Location $location)
    {
        $location = $location->query();

        if ($request->filled('filter')) {
            $location->where('name', 'like', "%{$request->filter}%");
        }

        if ($request->boolean('deleted')) {
            $location->onlyTrashed();
        }

        if ($request->boolean('active')) {
            $location->onlyActive();
        }

        if ($request->boolean('page')) {
            $location = $location->orderBy($request->sortBy ?? 'name', $request->direction ?? 'asc')
                ->paginate($request->rowsPerPage ?? 15);
            return new ResourceCollection($location);
        } else {
            return response()->json([
                'data' => $location->get()
            ], 200);
        }
    }

    public function store(Request $request, Location $location)
    {
        $rules = [
            'name' => 'required',
        ];

        // Validate those rules
        $this->validate($request, $rules);

        // create the location
        $location = Location::create($request->input());

        return response()->json([
            'data' => $location,
            'message' => trans_module('store', 'location'),
        ], 200);
    }

    public function update(Request $request, Location $location)
    {
        $rules = [
            'name' => 'required',
        ];

        // Validate those rules
        $this->validate($request, $rules);

        // update the location
        $location->update($request->input());

        return response()->json([
            'data' => $location->fresh(),
            'message' => trans_module('updated', 'location'),
        ], 200);
    }

    public function destroy(Location $location)
    {
        $location->delete();
        return response()->json([
            'message' => trans_module('destroy', 'location'),
        ], 200);
    }
}
