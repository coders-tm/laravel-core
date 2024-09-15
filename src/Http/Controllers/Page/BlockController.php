<?php

namespace Coderstm\Http\Controllers\Page;

use Illuminate\Http\Request;
use Coderstm\Models\Page\Block;
use Coderstm\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BlockController extends Controller
{
    /**
     * Create the controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->authorizeResource(Block::class);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $block = Block::query();

        $block = $block->orderBy($request->sortBy ?? 'created_at', $request->direction ?? 'desc');

        if ($request->isNotFilled('rowsPerPage')) {
            return $block->get();
        }

        return new ResourceCollection($block->paginate($request->rowsPerPage ?? 15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Set rules
        $rules = [
            'data' => 'array|required',
            'data.key' => 'string|required',
            'data.options' => 'array|required',
        ];

        // Validate those rules
        $request->validate($rules);

        // create the block
        $block = Block::create($request->input());

        return response()->json([
            'data' => $block,
            'message' => trans_module('store', 'block'),
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Block $block)
    {
        $block->delete();

        return response()->json([
            'message' => trans_module('destroy', 'block'),
        ], 200);
    }
}
