<?php

namespace App\Http\Controllers\Product;

use Illuminate\Http\Request;
use Coderstm\Services\Resource;
use Coderstm\Models\Shop\Product;
use Coderstm\Models\Shop\Product\Variant;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\ResourceCollection;

class VariantController extends Controller
{
    public function index(Request $request, Product $product)
    {
        $variant = $product->variants()->with('inventories', 'inventories.location:id,name', 'options')->where('is_default', 0);

        if ($request->input('deleted') ? $request->boolean('deleted') : false) {
            $variant->onlyTrashed();
        }

        $variant->orderBy($request->sortBy ?? 'created_at', $request->direction ?? 'desc');

        if ($request->filled('rowsPerPage')) {
            return new ResourceCollection($variant->paginate($request->rowsPerPage ?? 50));
        } else {
            return new ResourceCollection($variant->get());
        }
    }

    public function show(Variant $variant)
    {
        return response()->json($variant->load('recurringPlans'), 200);
    }

    public function update(Request $request, Variant $variant)
    {
        // Validate those rules
        $request->validate([
            'barcode' => "nullable|unique:variants,barcode,{$variant->id}",
        ]);

        // Update variant
        $variant->update($request->input());

        $variant->saveRelated(new Resource($request->input()));

        return response()->json([
            'data' => $variant->fresh('recurringPlans'),
            'message' => 'Variant has been updated successfully!',
        ], 200);
    }
}
