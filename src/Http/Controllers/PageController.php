<?php

namespace Coderstm\Http\Controllers;

use Coderstm\Models\Page;
use Illuminate\Http\Request;
use Coderstm\Http\Controllers\Controller;
use Coderstm\Http\Resources\PageCollection;

class PageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = Page::query()->select([
            'id',
            'title',
            'slug',
            'meta_title',
            'meta_keywords',
            'meta_description',
            'is_active',
            'template',
        ]);

        if ($request->filled('filter')) {
            $page->where(function ($query) use ($request) {
                $query->where('title', 'like', "%{$request->filter}%")
                    ->orWhere('body', 'like', "%{$request->filter}%");
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

        $page = $page->orderBy($request->sortBy ?? 'created_at', $request->direction ?? 'desc')
            ->paginate($request->rowsPerPage ?? 15);
        return new PageCollection($page);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Set rules
        $rules = [
            'title' => 'required',
            'data' => 'array',
            'html' => 'array',
            'html.body' => 'string',
            'html.css' => 'string',
            'meta_keywords' => 'max:255',
            'meta_description' => 'max:255',
        ];

        // Validate those rules
        $request->validate($rules);

        // create the page
        $page = Page::create($request->input());

        if ($request->filled('template')) {
            $page->setTemplate($request->template);
        }

        return response()->json([
            'data' => $page,
            'message' => trans_module('store', 'page'),
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(Page $page)
    {
        return response()->json($page, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Page $page)
    {
        // Set rules
        $rules = [
            'title' => 'string',
            'data' => 'array',
            'html' => 'array',
            'html.body' => 'string',
            'html.css' => 'string',
            'meta_keywords' => 'max:255',
            'meta_description' => 'max:255',
        ];

        // Validate those rules
        $request->validate($rules);

        // update the page
        $page->update($request->input());

        if ($request->filled('template')) {
            $page->setTemplate($request->template);
        }

        if ($request->boolean('publish')) {
            $page->publish($request->html);
        }

        return response()->json([
            'data' => $page->fresh(),
            'message' => trans_module('updated', 'page'),
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Page $page)
    {
        $page->delete();
        return response()->json([
            'message' => trans_module('destroy', 'page'),
        ], 200);
    }

    /**
     * Remove the selected resource from storage.
     */
    public function destroySelected(Request $request, Page $page)
    {
        $this->validate($request, [
            'items' => 'required',
        ]);

        Page::whereIn('id', $request->items)->each(function ($item) {
            $item->delete();
        });

        return response()->json([
            'message' => trans_modules('destroy', 'page'),
        ], 200);
    }

    /**
     * Restore the specified resource from storage.
     */
    public function restore($id)
    {
        $page = Page::onlyTrashed()->where('id', $id)->firstOrFail();

        $page->restore();

        return response()->json([
            'message' => trans_module('restored', 'page'),
        ], 200);
    }

    /**
     * Remove the selected resource from storage.
     */
    public function restoreSelected(Request $request)
    {
        $this->validate($request, [
            'items' => 'required',
        ]);

        Page::onlyTrashed()
            ->whereIn('id', $request->items)->each(function ($item) {
                $item->restore();
            });

        return response()->json([
            'message' => trans_modules('restored', 'page'),
        ], 200);
    }

    public function changeActive(Request $request, Page $page)
    {
        $page->update([
            'is_active' => !$page->is_active
        ]);

        return response()->json([
            'message' => $page->is_active ? 'Page marked as active successfully!' : 'Page marked as deactivated successfully!',
        ], 200);
    }
}
