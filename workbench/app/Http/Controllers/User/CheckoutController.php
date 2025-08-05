<?php

namespace App\Http\Controllers\User;

use Coderstm\Models\Shop\Product;
use Illuminate\Http\Request;
use Coderstm\Models\Shop\Checkout;
use Coderstm\Models\Subscription\Plan;
use Coderstm\Models\Shop\Product\Variant;
use Coderstm\Repository\CheckoutRepository;
use App\Http\Controllers\Controller;

class CheckoutController extends Controller
{

    public function index(Request $request, $token = null)
    {
        if ($token) {
            $checkout = Checkout::where('token', $token)->firstOrFail();

            // If checkout is completed, redirect to order confirmation
            if ($checkout->status === 'completed') {
                return response()->json([
                    'redirect' => true,
                    'url' => "/shop/order-confirmation/{$checkout->order->id}",
                ], 200);
            }
        }

        $repository = CheckoutRepository::fromRequest($request, $checkout ?? null);

        // Smart calculation - only recalculate if needed
        $repository->calculate();

        return response()->json($repository->getCheckoutData(), 200);
    }

    public function subscription(Request $request)
    {
        $request->validate([
            'variant_id' => 'nullable|exists:variants,id',
            'plan_id' => 'required|exists:plans,id',
            'quantity' => 'nullable|integer|min:1|max:1',
            'coupon_code' => 'nullable|string',
        ], [
            'variant_id.required' => 'The variant field is required for subscription products.',
            'plan_id.required' => 'The plan field is required for subscription products.',
            'quantity.max' => 'Subscription products are limited to quantity of 1.',
        ]);

        $lineItems = [
            [
                'variant_id' => $request->input('variant_id'),
                'plan_id' => $request->input('plan_id'),
                'quantity' => 1, // Subscription products are always quantity 1
            ],
        ];

        // Map line items first without auto-coupon logic
        $lineItems = collect($lineItems)->map(function ($item) {
            $variant = $item['variant_id'] ? Variant::find($item['variant_id']) : null;
            $plan = Plan::find($item['plan_id']);
            $product = $variant ? $variant->product : Product::find($variant->product_id);

            // Validate that the plan belongs to this product/variant
            if ($variant) {
                // If variant exists, plan should belong to variant
                $planExists = $variant->recurringPlans()->where('id', $plan->id)->exists();
                if (!$planExists) {
                    throw new \InvalidArgumentException("Plan does not belong to the selected product.");
                }
            } else {
                throw new \InvalidArgumentException("Plan does not belong to the selected product.");
            }

            // Use original plan price (not effective price) for transparency
            $basePrice = $plan->price;
            $options = $variant ? $variant->getOptions() : [];

            return [
                'product_id' => $product->id,
                'variant_id' => $item['variant_id'] ?? null,
                'plan_id' => $item['plan_id'],
                'quantity' => 1,
                'title' => $product->title,
                'slug' => $product->slug,
                'price' => $basePrice, // Original price preserved
                'taxable' => true,
                'discount' => null, // No line item discount for trials
                'has_variant' => $product->has_variant,
                'variant_title' => $variant?->title,
                'sku' => $variant?->sku ?? $product->sku,
                'options' => $options,
                'plan' => [
                    'id' => $plan->id,
                    'label' => $plan->label,
                ],
            ];
        })->toArray();

        $request->merge([
            'status' => 'draft',
            'line_items' => $lineItems,
            'type' => 'subscription',
        ]);

        // Get or create subscription checkout
        $checkout = Checkout::getOrCreate($request, [
            'type' => 'subscription',
            'note' => $request->note,
        ]);

        $repository = CheckoutRepository::fromRequest($request, $checkout ?? null);

        // Calculate and save totals
        $repository->calculate();

        return response()->json($repository->getCheckoutData(), 200);
    }

    public function update(Request $request, $token = null)
    {
        if ($token) {
            $checkout = Checkout::where('token', $token)->firstOrFail();
        } else {
            $checkout = Checkout::getOrCreate($request);
        }

        if ($checkout->status === 'completed') {
            return response()->json([
                'message' => 'Checkout is already completed',
            ], 400);
        }

        // Update only provided fields (filter out null/empty values for progressive saving)
        $updateData = array_filter($request->only([
            'email',
            'first_name',
            'last_name',
            'phone_number',
            'shipping_address',
            'billing_address',
            'same_as_billing',
            'note'
        ]), function ($value, $key) {
            // Never allow same_as_billing to be null - let it keep its default/current value
            if ($key === 'same_as_billing' && $value === null) {
                return false;
            }
            return $value !== null && $value !== '' && $value !== [];
        }, ARRAY_FILTER_USE_BOTH);

        $checkout->update($updateData);

        // Use fromCheckout to preserve existing state, don't use fromRequest for updates
        $repository = CheckoutRepository::fromCheckout($checkout);
        $repository->calculate();

        return response()->json([
            'data' => $repository->getCheckoutData(),
            'message' => 'Checkout updated successfully!',
        ], 200);
    }

    public function show(Request $request, $token)
    {
        $checkout = Checkout::where('token', $token)->firstOrFail();
        $repository = CheckoutRepository::fromRequest($request, $checkout);

        // Use read-only data for show operations to avoid unnecessary calculations
        return response()->json($repository->getCheckoutData(false), 200);
    }

    public function applyCoupon(Request $request, $token)
    {
        $request->validate([
            'coupon_code' => 'required|string',
        ]);

        $checkout = Checkout::where('token', $token)->firstOrFail();
        $repository = CheckoutRepository::fromRequest($request, $checkout);

        if ($checkout->status === 'completed') {
            return response()->json(['message' => 'Checkout is already completed'], 400);
        }

        // Check if checkout has trial plans
        if ($repository->hasTrialPlans()) {
            return response()->json([
                'message' => 'Coupons cannot be applied when free trial discounts are active',
            ], 422);
        }

        try {
            $result = $repository->applyCoupon($request->coupon_code);

            // Recalculate and save totals after applying coupon
            $repository->calculate();

            return response()->json([
                'data' => $repository->getCheckoutData(),
                'application_level' => $result['application_level'],
                'message' => 'Coupon applied successfully!'
            ], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while applying the coupon',
            ], 500);
        }
    }

    public function removeCoupon(Request $request, $token)
    {
        $checkout = Checkout::where('token', $token)->firstOrFail();

        if ($checkout->status === 'completed') {
            return response()->json(['message' => 'Checkout is already completed'], 400);
        }

        $repository = CheckoutRepository::fromRequest($request, $checkout);

        // Clear all line item discounts when user removes coupon
        $repository = $repository->removeAllLineItemDiscounts();

        // Recalculate and save totals after removing coupon, but do NOT apply auto-coupon
        $repository->calculate(false);

        return response()->json($repository->getCheckoutData(), 200);
    }
}
