<?php

namespace Coderstm\Http\Controllers;

use Coderstm\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Gate;

class BlogController extends Controller
{
    use \Coderstm\Traits\HasResourceActions;

    public function __construct()
    {
        $this->useModel(Blog::class);
        $this->authorizeResource(Blog::class, 'blog', ['except' => ['show']]);
    }

    public function index(Request $request)
    {
        $blog = Blog::query();
        if ($request->filled('filter')) {
            $blog->where('title', 'like', "%{$request->filter}%");
        }
        if ($request->boolean('active')) {
            $blog->onlyActive();
        }
        if ($request->boolean('deleted') ?: false) {
            $blog->onlyTrashed();
        }
        $blogs = $blog->orderBy(optional($request)->sortBy ?? 'created_at', optional($request)->direction ?? 'desc')->paginate(optional($request)->rowsPerPage ?? 15);

        return new ResourceCollection($blogs);
    }

    public function store(Request $request)
    {
        $rules = ['title' => 'required', 'description' => 'required', 'meta_keywords' => 'max:255', 'meta_description' => 'max:255', 'thumbnail' => 'array', 'thumbnail.id' => 'sometimes|required_unless:thumbnail.src,null|integer', 'thumbnail.src' => 'required_if:thumbnail.id,null|string'];
        $this->validate($request, $rules);
        $blog = Blog::create($request->input());
        $this->saveRelated($request, $blog);

        return response()->json(['data' => $blog->fresh(['thumbnail', 'tags', 'comments']), 'message' => __('Blog has been created successfully!')], 200);
    }

    public function show($blog)
    {
        $blog = Blog::withTrashed()->findOrFail($blog);
        Gate::authorize('view', $blog);

        return response()->json($blog->load('comments.user'), 200);
    }

    public function update(Request $request, Blog $blog)
    {
        $rules = ['title' => 'required', 'description' => 'required', 'meta_keywords' => 'max:255', 'meta_description' => 'max:255', 'thumbnail' => 'array', 'thumbnail.id' => 'sometimes|required_unless:thumbnail.src,null|integer', 'thumbnail.src' => 'required_if:thumbnail.id,null|string'];
        $request->validate($rules);
        $blog->update($request->input());
        $this->saveRelated($request, $blog);

        return response()->json(['data' => $blog->fresh(['thumbnail', 'tags', 'comments']), 'message' => __('Blog has been updated successfully!')], 200);
    }

    public function changeActive(Request $request, Blog $blog)
    {
        $blog->update(['is_active' => ! $blog->is_active]);

        return response()->json(['message' => $blog->is_active ? 'Blog marked as active successfully!' : 'Blog marked as deactivated successfully!'], 200);
    }

    public function comments(Request $request, Blog $blog)
    {
        $rules = ['message' => 'required'];
        $this->validate($request, $rules);
        $comment = $blog->createComment($request->input());

        return response()->json(['data' => $comment->load('user'), 'message' => __('Comment has been added successfully!')], 200);
    }

    protected function saveRelated(Request $request, Blog $blog)
    {
        if ($request->filled('thumbnail')) {
            $blog->thumbnail()->sync([$request->input('thumbnail.id') => ['type' => 'thumbnail']]);
        }
        if ($request->filled('tags')) {
            $blog->setTags($request->input('tags'));
        }
    }
}
