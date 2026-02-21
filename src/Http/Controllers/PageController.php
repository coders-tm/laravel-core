<?php

namespace Coderstm\Http\Controllers;

use Coderstm\Http\Resources\PageResource;
use Coderstm\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    use \Coderstm\Traits\HasResourceActions;

    public function __construct()
    {
        $this->useModel(Page::class);
    }

    public function index(Request $request)
    {
        $page = Page::query()->select(['id', 'title', 'slug', 'parent', 'meta_title', 'meta_keywords', 'meta_description', 'is_active', 'created_at', 'updated_at']);
        if ($request->filled('filter')) {
            $page->where(function ($query) use ($request) {
                $query->where('title', 'like', "%{$request->filter}%");
            });
        }
        if ($request->boolean('option')) {
            $page->select('id', 'title');
        }
        if ($request->boolean('active')) {
            $page->onlyActive();
        }
        if ($request->boolean('deleted')) {
            $page->onlyTrashed();
        }
        $page = $page->orderBy($request->sortBy ?? 'created_at', $request->direction ?? 'desc')->paginate($request->rowsPerPage ?? 15);

        return PageResource::collection($page);
    }

    public function store(Request $request)
    {
        $rules = ['title' => 'required|string|max:255', 'slug' => 'nullable|string|max:255', 'data' => 'nullable|array', 'html' => 'nullable|array', 'html.body' => 'nullable|string', 'html.css' => 'nullable|string', 'meta_title' => 'nullable|string|max:255', 'meta_keywords' => 'nullable|string|max:255', 'meta_description' => 'nullable|string|max:255', 'template' => 'nullable|string|max:100', 'is_active' => 'nullable|boolean', 'publish' => 'nullable|boolean'];
        $request->validate($rules);
        $page = Page::create($request->only(['title', 'slug', 'html', 'meta_title', 'meta_keywords', 'meta_description', 'template', 'is_active']));
        $page->publishJson($request->data ?? []);
        if ($request->filled('template')) {
            $page->setTemplate($request->template);
        }

        return response()->json(['data' => $page, 'message' => trans_module('store', 'page')], 200);
    }

    public function show($page)
    {
        $page = Page::withTrashed()->findOrFail($page);

        return response()->json($page, 200);
    }

    public function update(Request $request, Page $page)
    {
        $rules = ['title' => 'sometimes|required|string|max:255', 'slug' => 'sometimes|nullable|string|max:255', 'data' => 'sometimes|nullable|array', 'html' => 'sometimes|nullable|array', 'html.body' => 'sometimes|nullable|string', 'html.css' => 'sometimes|nullable|string', 'meta_title' => 'sometimes|nullable|string|max:255', 'meta_keywords' => 'sometimes|nullable|string|max:255', 'meta_description' => 'sometimes|nullable|string|max:255', 'template' => 'sometimes|nullable|string|max:100', 'is_active' => 'sometimes|nullable|boolean', 'publish' => 'sometimes|nullable|boolean'];
        $request->validate($rules);
        $page->update($request->only(['title', 'slug', 'html', 'meta_title', 'meta_keywords', 'meta_description', 'template', 'is_active']));
        $page->publishJson($request->data ?? []);
        if ($request->filled('template')) {
            $page->setTemplate($request->template);
        }
        if ($request->boolean('publish')) {
            $page->publish($request->html);
        }

        return response()->json(['data' => $page->fresh(), 'message' => trans_module('updated', 'page')], 200);
    }

    public function changeActive(Request $request, Page $page)
    {
        $page->update(['is_active' => ! $page->is_active]);

        return response()->json(['message' => $page->is_active ? 'Page marked as active successfully!' : 'Page marked as deactivated successfully!'], 200);
    }
}
