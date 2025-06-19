<?php

namespace Coderstm\Http\Controllers\Blog;

use Illuminate\Http\Request;
use Coderstm\Models\Blog\Tag;
use Coderstm\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TagController extends Controller
{
    public function index(Request $request, Tag $tag)
    {
        $tag = $tag->query();

        $tag->when($request->boolean('options'), function ($query) {
            $query->withOptions();
        })->when($request->filled('filter'), function ($query) use ($request) {
            $query->where('label', 'like', "%{$request->filter}%");
        });

        $tags = $tag->orderBy($request->sortBy ?? 'created_at', $request->direction ?? 'desc')
            ->paginate($request->rowsPerPage ?? 15);

        // Regular tag results
        return new ResourceCollection($tags);
    }

    public function store(Request $request, Tag $tag)
    {
        // Set rules
        $rules = [
            'label' => 'required',
        ];

        // Validate those rules
        $this->validate($request, $rules);

        $tag = $tag->create($request->input());

        return response()->json([
            'data' => $tag,
            'message' => 'Tag has been created successfully!',
        ], 200);
    }
}
