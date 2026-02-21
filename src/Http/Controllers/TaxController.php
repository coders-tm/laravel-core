<?php

namespace Coderstm\Http\Controllers;

use Coderstm\Models\Tax;
use Illuminate\Http\Request;

class TaxController extends Controller
{
    use \Coderstm\Traits\HasResourceActions;

    public function __construct()
    {
        $this->useModel(Tax::class);
    }

    public function index(Request $request)
    {
        $tax = Tax::query();

        return $tax->orderBy($request->sortBy ?? 'code', $request->direction ?? 'desc')->orderBy('priority')->get();
    }

    public function store(Request $request)
    {
        $rules = ['country' => 'required', 'code' => 'required', 'state' => 'required', 'label' => 'required', 'rate' => 'required'];
        $request->validate($rules);
        $tax = Tax::create($request->input());

        return response()->json(['data' => $tax, 'message' => trans_module('store', 'tax')], 200);
    }

    public function update(Request $request, Tax $tax)
    {
        $rules = ['country' => 'required', 'code' => 'required', 'state' => 'required', 'label' => 'required', 'rate' => 'required'];
        $request->validate($rules);
        $tax->update($request->input());

        return response()->json(['data' => $tax->fresh(), 'message' => trans_module('updated', 'tax')], 200);
    }
}
