<?php

namespace Coderstm\Http\Controllers;

use Coderstm\Models\Task;
use Illuminate\Http\Request;
use Coderstm\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TaskController extends Controller
{
    /**
     * Create the controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->authorizeResource(Task::class);
    }

    private function query(Request $request, Task $task)
    {
        $task = $task->with('user');

        if ($request->filled('filter')) {
            $task->where('subject', 'like', "%{$request->filter}%");
        }

        $task->onlyStatus($request->status);

        if ($request->boolean('deleted')) {
            $task->onlyTrashed();
        }

        return $task->sortBy(optional($request)->sortBy ?? 'created_at', optional($request)->direction ?? 'desc');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Task $task)
    {
        $task = $this->query($request, $task)->onlyOwner()->paginate(optional($request)->rowsPerPage ?: 15);
        return new ResourceCollection($task);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Task $task)
    {
        $rules = [
            'subject' => 'required',
            'message' => 'required',
            'users' => 'required|array',
        ];

        $this->validate($request, $rules);

        $task = $task->create($request->input());

        // Update media
        if ($request->filled('media')) {
            $task = $task->syncMedia($request->input('media'));
        }

        $task->syncUsers(collect($request->users));

        return response()->json([
            'data' => $task->load(['users', 'replies.user', 'media']),
            'message' => trans('coderstm::messages.tasks.store'),
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \Coderstm\Models\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Task $task)
    {
        return response()->json($task->load(['users', 'replies.user', 'media']), 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Coderstm\Models\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Task $task)
    {
        $task->delete();
        return response()->json([
            'message' => trans_choice('coderstm::messages.tasks.destroy', 1),
        ], 200);
    }

    /**
     * Remove the selected resource from storage.
     *
     * @param  \Coderstm\Models\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function destroy_selected(Request $request, Task $task)
    {
        $this->validate($request, [
            'items' => 'required',
        ]);
        $task->whereIn('id', $request->items)->each(function ($item) {
            $item->delete();
        });
        return response()->json([
            'message' => trans_choice('coderstm::messages.tasks.destroy', 2),
        ], 200);
    }

    /**
     * Restore the specified resource from storage.
     *
     * @param  \Coderstm\Models\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function restore($id)
    {
        Task::onlyTrashed()
            ->where('id', $id)->each(function ($item) {
                $item->restore();
            });
        return response()->json([
            'message' => trans_choice('coderstm::messages.tasks.restored', 1),
        ], 200);
    }

    /**
     * Remove the selected resource from storage.
     *
     * @param  \Coderstm\Models\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function restore_selected(Request $request, Task $task)
    {
        $this->validate($request, [
            'items' => 'required',
        ]);
        $task->onlyTrashed()
            ->whereIn('id', $request->items)->each(function ($item) {
                $item->restore();
            });
        return response()->json([
            'message' => trans_choice('coderstm::messages.tasks.restored', 2),
        ], 200);
    }

    /**
     * Create reply for the specified resource.
     *
     * @param  \Coderstm\Models\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function reply(Request $request, Task $task)
    {
        $request->validate([
            'message' => 'required',
        ]);

        $reply = $task->createReply($request->input());

        // Update media
        if ($request->filled('media')) {
            $reply = $reply->syncMedia($request->input('media'));
        }

        // Update task status
        if ($request->filled('status')) {
            $task->update($request->only(['status']));
        }

        return response()->json([
            'data' => $reply->fresh(['media', 'user']),
            'message' => trans('coderstm::messages.tasks.reply'),
        ], 200);
    }

    /**
     * Change archived of specified resource from storage.
     *
     * @param  \Coderstm\Models\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function changeArchived(Request $request, Task $task)
    {
        if (!$task->is_archived) {
            $task->archives()->attach(current_user());
        } else {
            $task->archives()->detach(current_user());
        }

        $type = !$task->is_archived ? 'archived' : 'unarchive';

        return response()->json([
            'message' => trans('coderstm::messages.tasks.status', ['type' => trans('coderstm::messages.attributes.' . $type)]),
        ], 200);
    }
}
