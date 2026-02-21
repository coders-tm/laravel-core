<?php

namespace Coderstm\Http\Controllers;

use Coderstm\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    use \Coderstm\Traits\HasResourceActions;

    public function __construct()
    {
        $this->useModel(Task::class);
        $this->authorizeResource(Task::class, 'task', ['except' => ['show']]);
    }

    private function query(Request $request)
    {
        $task = Task::with('user');
        if ($request->filled('filter')) {
            $task->where('subject', 'like', "%{$request->filter}%");
        }
        $task->onlyStatus($request->status);
        if ($request->boolean('deleted')) {
            $task->onlyTrashed();
        }

        return $task->sortBy($request->sortBy ?? 'created_at', $request->direction ?? 'desc');
    }

    public function index(Request $request)
    {
        $task = $this->query($request)->onlyOwner()->paginate($request->rowsPerPage ?: 15);

        return new ResourceCollection($task);
    }

    public function store(Request $request, Task $task)
    {
        $rules = ['subject' => 'required', 'message' => 'required', 'users' => 'required|array'];
        $request->validate($rules);
        $task = $task->create($request->input());
        if ($request->filled('media')) {
            $task = $task->syncMedia($request->input('media'));
        }
        $task->syncUsers(collect($request->users));

        return response()->json(['data' => $task->load('user', 'replies.user', 'media'), 'message' => __('Task has been created successfully!')], 200);
    }

    public function show(Request $request, $task)
    {
        $task = Task::withTrashed()->findOrFail($task);
        Gate::authorize('view', $task);

        return response()->json($task->load(['users', 'replies.user', 'media']), 200);
    }

    public function reply(Request $request, Task $task)
    {
        $request->validate(['message' => 'required']);
        $reply = $task->createReply($request->input());
        if ($request->filled('media')) {
            $reply = $reply->syncMedia($request->input('media'));
        }
        if ($request->filled('status')) {
            $task->update($request->only(['status']));
        }

        return response()->json(['data' => $reply->fresh('user'), 'message' => __('Reply has been created successfully!')], 200);
    }

    public function changeArchived(Request $request, Task $task)
    {
        if (! $task->is_archived) {
            $task->archives()->attach(user());
        } else {
            $task->archives()->detach(user());
        }
        $type = ! $task->is_archived ? 'archived' : 'unarchive';

        return response()->json(['message' => __('Task marked as :type successfully!', ['type' => __($type)])], 200);
    }
}
