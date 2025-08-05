<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use Coderstm\Models\Shop\Checkout;
use App\Http\Controllers\Controller;
use Coderstm\Repository\CheckoutRepository;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $checkout = Checkout::getOrCreate($request, [
            'type' => 'standard',
            'status' => 'draft',
        ]);

        $repository = CheckoutRepository::fromRequest($request, $checkout);

        return response()->json($repository->getCartItems(), 200);
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'required|exists:variants,id',
            'quantity' => 'integer|min:1',
            'metadata' => 'nullable|array',
        ]);

        $checkout = Checkout::getOrCreate($request, [
            'type' => 'standard',
            'status' => 'draft',
        ]);

        // Ensure checkout is saved first to get an ID for relationships
        if (!$checkout->exists) {
            $checkout->save();
        }

        // Find existing item with same product and variant
        $existingItem = $checkout->line_items()
            ->where('product_id', $request->product_id)
            ->where('variant_id', $request->variant_id)
            ->first();

        if ($existingItem) {
            // Update quantity of existing item
            $existingItem->update([
                'quantity' => $existingItem->quantity + ($request->quantity ?? 1)
            ]);
        } else {
            // Add new item
            $product = \Coderstm\Models\Shop\Product::find($request->product_id);
            $variant = \Coderstm\Models\Shop\Product\Variant::find($request->variant_id);
            $options = $variant->getOptions();

            $checkout->line_items()->create([
                'product_id' => $request->product_id,
                'variant_id' => $request->variant_id,
                'quantity' => $request->quantity ?? 1,
                'title' => $product->title,
                'slug' => $product->slug,
                'price' => $variant->price,
                'taxable' => true,
                'has_variant' => $product->has_variant,
                'variant_title' => $variant->title,
                'sku' => $variant->sku,
                'options' => $options,
            ]);
        }

        // Refresh checkout to ensure line items are up-to-date
        $checkout->refresh(['line_items']);

        $repository = CheckoutRepository::fromRequest($request, $checkout);

        // Calculate and save totals after adding item (auto-coupons handled in fromRequest)
        $repository->calculate();

        return response()->json([
            'data' => $repository->getCartItems(),
            'message' => 'Product has been added to cart successfully!',
        ], 200);
    }

    public function update(Request $request, $itemId)
    {
        $checkout = Checkout::getOrCreate($request, [
            'type' => 'standard',
            'status' => 'draft',
        ]);

        // Find item by ID from relationships
        $lineItem = $checkout->line_items()->where('id', $itemId)->first();

        if (!$lineItem) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        // Update quantity
        $lineItem->update(['quantity' => $request->quantity]);

        // Refresh checkout to ensure line items are up-to-date
        $checkout->refresh(['line_items']);

        $repository = CheckoutRepository::fromRequest($request, $checkout);

        // Calculate and save totals after updating item (auto-coupons handled in fromRequest)
        $repository->calculate();

        return response()->json([
            'data' => $repository->getCartItems(),
            'message' => 'Cart updated successfully!',
        ], 200);
    }

    public function remove(Request $request, $itemId)
    {
        $checkout = Checkout::getOrCreate($request, [
            'type' => 'standard',
            'status' => 'draft',
        ]);

        // Find and delete item by ID from relationships
        $lineItem = $checkout->line_items()->where('id', $itemId)->first();

        if (!$lineItem) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        // Remove item
        $lineItem->delete();

        // Refresh checkout to ensure line items are up-to-date
        // This is necessary to ensure the repository has the latest state
        $checkout->refresh(['line_items']);

        $repository = CheckoutRepository::fromRequest($request, $checkout);

        // Calculate and save totals after removing item (auto-coupons handled in fromRequest)
        $repository->calculate();

        return response()->json([
            'data' => $repository->getCartItems(),
            'message' => 'Product has been removed from cart successfully!',
        ], 200);
    }

    public function clear(Request $request)
    {
        $checkout = Checkout::getOrCreate($request, [
            'type' => 'standard',
            'status' => 'draft',
        ]);

        // Clear all line items and discount through relationships
        $checkout->line_items()->forceDelete();
        $checkout->discount()->forceDelete();
        $checkout->tax_lines()->forceDelete();
        $checkout->coupon_code = null;
        $checkout->unsetRelations([
            'line_items',
            'discount',
            'tax_lines',
        ]);

        $repository = CheckoutRepository::fromRequest($request, $checkout);

        // Calculate and save totals after clearing cart
        $repository->calculate();

        return response()->json([
            'data' => $repository->getCartItems(),
            'message' => 'Cart has been cleared successfully!',
        ], 200);
    }
}
