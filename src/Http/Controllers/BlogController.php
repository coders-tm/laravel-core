<?php

namespace Coderstm\Http\Controllers;

use Coderstm\Models\Blog;
use Illuminate\Http\Request;
use Coderstm\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BlogController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Blog::class);
    }

    public function index(Request $request, Blog $blog)
    {
        $blog = $blog->query();

        if ($request->filled('filter')) {
            $blog->where('title', 'like', "%{$request->filter}%");
        }

        if ($request->boolean('active')) {
            $blog->onlyActive();
        }

        if ($request->boolean('deleted') ?: false) {
            $blog->onlyTrashed();
        }

        $blogs = $blog->orderBy(optional($request)->sortBy ?? 'created_at', optional($request)->direction ?? 'desc')
            ->paginate(optional($request)->rowsPerPage ?? 15);

        // Regular blog results
        return new ResourceCollection($blogs);
    }

    public function store(Request $request, Blog $blog)
    {
        // Set rules
        $rules = [
            'title' => 'required',
            'description' => 'required',
            // Images
            'media' => 'array',
            'media.*.id' => 'sometimes|required_unless:media.*.src,null|integer',
            'media.*.src' => 'required_if:media.*.id,null|string',
        ];

        // Validate those rules
        $this->validate($request, $rules);

        $blog = $blog->create($request->input());

        // save blog's realted model
        $this->saveRelated($request, $blog);

        return response()->json([
            'data' => $blog->fresh(['media', 'thumbnail', 'tags', 'comments']),
            'message' => 'Blog has been created successfully!',
        ], 200);
    }

    public function show(Blog $blog)
    {
        return response()->json($blog->load('comments.user'), 200);
    }

    public function update(Request $request, Blog $blog)
    {
        // Set rules
        $rules = [
            'title' => 'required',
            'description' => 'required',
            // Images
            'media' => 'array',
            'media.*.id' => 'sometimes|required_unless:media.*.src,null|integer',
            'media.*.src' => 'required_if:media.*.id,null|string',
        ];

        // Validate those rules
        $this->validate($request, $rules);

        $blog->update($request->input());

        // save blog's realted model
        $this->saveRelated($request, $blog);

        return response()->json([
            'data' => $blog->fresh(['media', 'thumbnail', 'tags', 'comments']),
            'message' => 'Blog has been updated successfully!',
        ], 200);
    }

    public function destroy(Blog $blog)
    {
        $blog->delete();
        return response()->json([
            'message' => 'Blog has been deleted successfully!',
        ], 200);
    }

    public function destroySelected(Request $request, Blog $blog)
    {
        $this->validate($request, [
            'items' => 'required',
        ]);
        $blog->whereIn('id', $request->items)->each(function ($item) {
            $item->delete();
        });
        return response()->json([
            'message' => 'Blogs has been deleted successfully!',
        ], 200);
    }

    public function restore($id)
    {
        Blog::onlyTrashed()
            ->where('id', $id)->each(function ($item) {
                $item->restore();
            });
        return response()->json([
            'message' => 'Blog has been restored successfully!',
        ], 200);
    }

    public function restoreSelected(Request $request, Blog $blog)
    {
        $this->validate($request, [
            'items' => 'required',
        ]);
        $blog->onlyTrashed()
            ->whereIn('id', $request->items)->each(function ($item) {
                $item->restore();
            });
        return response()->json([
            'message' => 'Blogs has been restored successfully!',
        ], 200);
    }

    public function changeActive(Request $request, Blog $blog)
    {
        $blog->update([
            'is_active' => !$blog->is_active
        ]);

        return response()->json([
            'message' => $blog->is_active ? 'Blog marked as active successfully!' : 'Blog marked as deactivated successfully!',
        ], 200);
    }

    public function comments(Request $request, Blog $blog)
    {
        $rules = [
            'message' => 'required',
        ];

        // Validate those rules
        $this->validate($request, $rules);

        $comment = $blog->createComment($request->input());

        return response()->json([
            'data' => $comment->load('user'),
            'message' => 'Comment has been added successfully!',
        ], 200);
    }

    protected function saveRelated(Request $request, Blog $blog)
    {
        // Update media
        if ($request->filled('media')) {
            $blog->syncMedia($request->input('media'));
        }

        // Update tags
        if ($request->filled('tags')) {
            $blog->setTags($request->input('tags'));
        }
    }
}
