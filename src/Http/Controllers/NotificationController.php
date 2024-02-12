<?php

namespace Coderstm\Http\Controllers;

use Illuminate\Http\Request;
use Coderstm\Http\Controllers\Controller;
use Coderstm\Models\Notification;
use Illuminate\Http\Resources\Json\ResourceCollection;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Notification::class);
    }

    public function index(Request $request, Notification $notification)
    {
        $notification = $notification->query();

        if ($request->has('filter') && !empty($request->filter)) {
            $notification->where('label', 'like', "%{$request->filter}%");
            $notification->orWhere('type', 'like', "%{$request->filter}%");
        }

        if ($request->input('deleted') ? $request->boolean('deleted') : false) {
            $notification->onlyTrashed();
        }

        $notification = $notification->orderBy(optional($request)->sortBy ?? 'created_at', optional($request)->direction ?? 'desc')
            ->paginate(optional($request)->rowsPerPage ?? 15);
        return new ResourceCollection($notification);
    }

    public function store(Request $request, Notification $notification)
    {
        $rules = [
            'label' => 'required',
            'type' => 'required',
            'content' => 'required',
        ];

        $this->validate($request, $rules);

        $notification = $notification->create($request->all());

        if ($request->boolean('is_default')) {
            $notification->markAsDefault();
        }

        return response()->json([
            'data' => $notification,
            'message' => 'Notification has been created successfully!'
        ], 200);
    }

    public function show(Notification $notification)
    {
        return response()->json($notification, 200);
    }

    public function update(Request $request, Notification $notification)
    {
        $rules = [
            'label' => 'required',
            'type' => 'required',
            'content' => 'required',
        ];

        $this->validate($request, $rules);

        $notification->update($request->all());

        if ($request->boolean('is_default')) {
            $notification = $notification->markAsDefault();
        }

        return response()->json([
            'data' => $notification,
            'message' => 'Notification has been updated successfully!'
        ], 200);
    }

    public function destroy(Notification $notification)
    {
        if ($notification->default) {
            abort(422, 'Default notification can\'t be deleted');
        }

        $notification->delete();

        return response()->json([
            'message' => 'Notification has been deleted successfully!'
        ], 200);
    }

    public function destroySelected(Request $request, Notification $notification)
    {
        $this->validate($request, [
            'items' => 'required',
        ]);
        $notification->whereIn('id', $request->items)->delete();
        return response()->json([
            'message' => 'Notifications has been deleted successfully!',
        ], 200);
    }

    public function restore($id)
    {
        Notification::onlyTrashed()
            ->where('id', $id)
            ->restore();
        return response()->json([
            'message' => 'Notification has been restored successfully!',
        ], 200);
    }

    public function restoreSelected(Request $request, Notification $notification)
    {
        $this->validate($request, [
            'items' => 'required',
        ]);
        $notification->onlyTrashed()
            ->whereIn('id', $request->items)
            ->restore();
        return response()->json([
            'message' => 'Notifications has been restored successfully!',
        ], 200);
    }

    public function markAsDefault(Request $request, Notification $notification)
    {
        $notification->markAsDefault();

        return response()->json([
            'data' => $notification,
            'message' => 'Template marked as default successfully!',
        ], 200);
    }
}
