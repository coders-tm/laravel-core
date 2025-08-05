<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use Coderstm\Models\Coupon;
use Coderstm\Models\Shop\Product;
use App\Http\Controllers\Controller;
use Coderstm\Models\Shop\Product\Variant;
use Coderstm\Models\Shop\Product\Category;
use Coderstm\Models\Shop\Product\Attribute;

class ShopController extends Controller
{
    public function products(Request $request, Product $product)
    {
        $product = $product->query()->with(['media', 'category', 'default_variant.recurringPlans', 'variants.recurringPlans']);

        // Search filter
        if ($request->filled('search')) {
            $product->where('title', 'like', "%{$request->search}%");
        }

        // Legacy filter support
        if ($request->filled('filter')) {
            $product->where('title', 'like', "%{$request->filter}%");
        }

        // Category filter
        if ($request->filled('categories')) {
            $categories = is_array($request->categories) ? $request->categories : [$request->categories];
            $product->whereIn('category_id', $categories);
        }

        // Price range filter
        if ($request->filled('price_min')) {
            $product->whereHas('variants', function ($query) use ($request) {
                $query->where('price', '>=', $request->price_min);
            });
        }
        if ($request->filled('price_max')) {
            $product->whereHas('variants', function ($query) use ($request) {
                $query->where('price', '<=', $request->price_max);
            });
        }

        // Availability filter
        if ($request->filled('availability')) {
            switch ($request->availability) {
                case 'in_stock':
                    $product->whereHas('variants', function ($query) {
                        $query->where('track_inventory', false)
                            ->orWhere(function ($q) {
                                $q->where('track_inventory', true)
                                    ->whereHas('inventories', function ($inv) {
                                        $inv->where('active', true)
                                            ->where('available', '>', 0);
                                    });
                            });
                    });
                    break;
                case 'out_of_stock':
                    $product->whereHas('variants', function ($query) {
                        $query->where('track_inventory', true)
                            ->whereDoesntHave('inventories', function ($inv) {
                                $inv->where('active', true)
                                    ->where('available', '>', 0);
                            });
                    });
                    break;
                case 'low_stock':
                    $product->whereHas('variants', function ($query) {
                        $query->where('track_inventory', true)
                            ->whereHas('inventories', function ($inv) {
                                $inv->where('active', true)
                                    ->where('available', '>', 0)
                                    ->where('available', '<=', 5);
                            });
                    });
                    break;
            }
        }

        // Attribute filters - commented out until product-attribute relationship is established
        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'attr_') && !empty($value)) {
                $attributeId = str_replace('attr_', '', $key);
                $values = is_array($value) ? $value : [$value];

                $product->whereHas('options', function ($query) use ($attributeId, $values) {
                    $query->where('attribute_id', $attributeId)
                        ->whereHas('attribue_values', function ($q) use ($values) {
                            $q->whereIn('name', $values);
                        })
                        ->orWhere(function ($q) use ($values) {
                            $q->where('is_custom', true)
                                ->whereJsonContains('custom_values', $values);
                        });
                });
            }
        }

        $product->onlyActive();

        $products = $product->sortBy($request->sortBy ?? 'created_at', $request->direction ?? 'desc')
            ->paginate($request->rowsPerPage ?? 15);

        // Map products to frontend format
        $mappedProducts = $products->getCollection()->map(function ($product) {
            return $this->mapProductForListing($product);
        });

        // Return response with proper structure for frontend
        return response()->json([
            'data' => $mappedProducts,
            'meta' => [
                'total' => $products->total(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'from' => $products->firstItem(),
                'to' => $products->lastItem(),
            ]
        ], 200);
    }

    public function product(Request $request, $slug)
    {
        $product = Product::whereSlug($slug)->first();

        if (!$product) {
            abort(404, trans('messages.product_not_found'));
        }

        $response = $product->load('media')->toArray();

        // Get the appropriate variant based on product type
        $variant = $this->variantFromProduct($product);

        if (!$variant && $product->has_variant) {
            abort(404, 'No variants found for this product');
        }

        // Build the complete product response
        $variantData = $this->buildVariantResponse($variant, $product);
        $response = array_merge($response, $variantData);

        // Add product-specific data
        if ($product->has_variant) {
            $variants = $product->variants()
                ->orderBy('id', 'asc')
                ->get();

            $response['variants'] = $variants->map(function ($variant) {
                return $variant->only(['id', 'title', 'in_stock', 'thumbnail']);
            });
        }

        // Remove default variant from response as it's not needed
        unset($response['default_variant']);

        return response()->json($response, 200);
    }

    public function variant(Request $request, Variant $variant)
    {
        $variant = $variant->load(['recurringPlans', 'product']);
        $variantData = $this->buildVariantResponse($variant);

        return response()->json($variantData, 200);
    }

    public function categories(Request $request)
    {
        $categories = Category::query()
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return response()->json($categories, 200);
    }

    public function attributes(Request $request)
    {
        $attributes = Attribute::with(['values' => function ($query) {
            $query->select('id', 'attribute_id', 'name');
        }])
            ->has('products')
            ->get(['id', 'name', 'slug']);

        // Transform attributes to include options for frontend
        $attributes = $attributes->map(function ($attribute) {
            return [
                'id' => $attribute->id,
                'name' => $attribute->name,
                'slug' => $attribute->slug,
                'options' => $attribute->values->map(function ($value) {
                    return [
                        'label' => $value->name,
                        'value' => $value->name,
                    ];
                })->toArray(),
            ];
        });

        return response()->json($attributes, 200);
    }

    /**
     * Get the appropriate variant for a product based on its type
     */
    private function variantFromProduct($product)
    {
        if ($product->has_variant) {
            // For products with variants, get the first available variant or lowest priced variant
            return $product->variants()
                ->stockIn()
                ->with(['recurringPlans'])
                ->orderBy('price', 'asc')
                ->first();
        } else {
            // For simple products, use the default variant
            return $product->default_variant?->loadMissing(['recurringPlans']);
        }
    }

    /**
     * Build comprehensive variant response data
     */
    private function buildVariantResponse($variant, $product = null)
    {
        if (!$variant) {
            return [
                'variant_id' => null,
                'in_stock' => true,
                'options' => [],
                'price' => 0,
                'price_formatted' => '$0.00',
                'plan' => null,
                'current_variant' => null,
                'recurring_plans' => [],
                'has_discount' => false,
            ];
        }

        $plan = $variant->recurringPlans->first();
        $product = $product ?? $variant->product;

        if ($variant->recurring && $plan) {
            // Handle recurring variants with plan-based pricing
            $response = $this->buildRecurringVariantResponse($variant, $plan);
        } else {
            // Handle non-recurring variants with variant-based pricing
            $response = $this->buildNonRecurringVariantResponse($variant);
        }

        // Add common variant data
        $response['variant_id'] = $variant->id;
        $response['in_stock'] = $variant->in_stock;
        $response['options'] = $variant->getOptionsWithValues();

        return $response;
    }

    /**
     * Build response for recurring variants (subscription-based)
     */
    private function buildRecurringVariantResponse($variant, $plan)
    {
        // Apply plan coupon and get pricing
        $planCoupon = $this->getBestPlanCoupon($plan);
        $planDiscount = $this->applyPlanDiscount($plan, $planCoupon);

        // Build plan data with discount information
        $planData = $this->buildPlanArray($plan, $planDiscount);

        // Build all recurring plans with their respective discounts
        $recurringPlans = $variant->recurringPlans->map(function ($plan) {
            $planCoupon = $this->getBestPlanCoupon($plan);
            $planDiscount = $this->applyPlanDiscount($plan, $planCoupon);
            return $this->buildPlanArray($plan, $planDiscount);
        })->toArray();

        // Build variant details (no product discounts for recurring variants)
        $variantDetails = $this->buildVariantDetails($variant);
        $variantDetails['discount'] = null;

        return [
            'price' => $planDiscount['price'],
            'price_formatted' => $planDiscount['price_formatted'],
            'plan' => $planData,
            'current_variant' => $variantDetails,
            'recurring_plans' => $recurringPlans,
            'has_discount' => !is_null($planDiscount['discount']),
        ];
    }

    /**
     * Build response for non-recurring variants (one-time purchase)
     */
    private function buildNonRecurringVariantResponse($variant)
    {
        // Apply product coupon and get pricing
        $productCoupon = $this->getBestProductCoupon($variant);
        $variantDiscount = $this->applyVariantDiscount($variant, $productCoupon);

        // Build variant details with discount information
        $variantDetails = $this->buildVariantDetails($variant);
        if ($variantDiscount['discount']) {
            $variantDetails = array_merge($variantDetails, [
                'discount' => $variantDiscount['discount'],
                'price' => $variantDiscount['price'],
                'price_formatted' => $variantDiscount['price_formatted'],
                'compare_at_price' => $variantDiscount['compare_at_price'],
                'compare_at_price_formatted' => $variantDiscount['compare_at_price_formatted'],
            ]);
        } else {
            $variantDetails['discount'] = null;
        }

        return [
            'price' => $variantDiscount['price'],
            'price_formatted' => $variantDiscount['price_formatted'],
            'plan' => null,
            'current_variant' => $variantDetails,
            'recurring_plans' => [],
            'has_discount' => !is_null($variantDiscount['discount']),
        ];
    }

    /**
     * Build plan array with discount information
     */
    private function buildPlanArray($plan, $planDiscount)
    {
        $planArray = $plan->toArray();

        if ($planDiscount['discount']) {
            $planArray = array_merge($planArray, [
                'discount' => $planDiscount['discount'],
                'price' => $planDiscount['price'],
                'price_formatted' => $planDiscount['price_formatted'],
                'compare_at_price' => $planDiscount['compare_at_price'],
                'compare_at_price_formatted' => $planDiscount['compare_at_price_formatted'],
            ]);
        } else {
            $planArray['discount'] = null;
        }

        return $planArray;
    }

    /**
     * Build variant details array
     */
    private function buildVariantDetails($variant)
    {
        return $variant->only([
            'id',
            'title',
            'price',
            'price_formatted',
            'compare_at_price',
            'in_stock',
            'media_id',
            'thumbnail',
            'recurring',
        ]);
    }

    /**
     * Get the best auto-applicable coupon for a product
     */
    private function getBestProductCoupon($variant)
    {
        $coupons = Coupon::autoApplicable()
            ->where('type', Coupon::TYPE_PRODUCT)
            ->get()
            ->filter(function ($coupon) use ($variant) {
                return $coupon->canApplyToProduct($variant->product_id);
            });

        if ($coupons->isEmpty()) {
            return null;
        }

        // Return the coupon with the highest discount amount
        return $coupons->sortByDesc(function ($coupon) use ($variant) {
            return $coupon->getDiscountPriority($variant->price);
        })->first();
    }

    /**
     * Get the best auto-applicable coupon for a plan
     */
    private function getBestPlanCoupon($plan)
    {
        if ($plan->hasTrial()) {
            return null; // Skip trial plans for coupon application
        }

        $coupons = Coupon::autoApplicable()
            ->where('type', Coupon::TYPE_PLAN)
            ->get()
            ->filter(function ($coupon) use ($plan) {
                return $coupon->canApplyToPlan($plan->id);
            });

        if ($coupons->isEmpty()) {
            return null;
        }

        // Return the coupon with the highest discount amount
        return $coupons->sortByDesc(function ($coupon) use ($plan) {
            return $coupon->getDiscountPriority($plan->price);
        })->first();
    }

    /**
     * Apply coupon discount to a variant
     */
    private function applyVariantDiscount($variant, $coupon)
    {
        if (!$coupon) {
            return [
                'discount' => null,
                'price' => $variant->price,
                'price_formatted' => $variant->price_formatted,
                'compare_at_price' => null,
                'compare_at_price_formatted' => null,
            ];
        }

        $originalPrice = $variant->price;
        $discountedPrice = $coupon->getFinalPrice($originalPrice);

        return [
            'discount' => [
                'coupon_code' => $coupon->promotion_code,
                'description' => $coupon->name,
                'type' => $coupon->discount_type,
                'value' => $coupon->value,
            ],
            'price' => $discountedPrice,
            'price_formatted' => format_amount($discountedPrice),
            'compare_at_price' => $originalPrice,
            'compare_at_price_formatted' => format_amount($originalPrice),
        ];
    }

    /**
     * Apply coupon discount to a plan
     */
    private function applyPlanDiscount($plan, $coupon)
    {
        if (!$coupon) {
            return [
                'discount' => null,
                'price' => $plan->price,
                'price_formatted' => $plan->price_formatted,
                'compare_at_price' => null,
                'compare_at_price_formatted' => null,
            ];
        }

        $originalPrice = $plan->price;
        $discountedPrice = $coupon->getFinalPrice($originalPrice);

        return [
            'discount' => [
                'coupon_code' => $coupon->promotion_code,
                'duration' => $coupon->duration,
                'duration_in_months' => $coupon->duration_in_months,
                'description' => $coupon->name,
                'type' => $coupon->discount_type,
                'value' => $coupon->value,
            ],
            'price' => $discountedPrice,
            'price_formatted' => format_amount($discountedPrice),
            'compare_at_price' => $originalPrice,
            'compare_at_price_formatted' => format_amount($originalPrice),
        ];
    }

    /**
     * Map product data for listing view to match frontend requirements
     */
    private function mapProductForListing($product)
    {
        // Load necessary relationships
        $product->loadMissing(['media', 'category']);

        // Get the appropriate variant for this product
        $variant = $this->variantFromProduct($product);

        // Build base product data
        $productData = $product->toArray();

        // Build variant response data
        $variantData = $this->buildVariantResponse($variant, $product);
        $productData = array_merge($productData, $variantData);

        // Add missing fields that tests expect
        $productData['recurring'] = $variant ? $variant->recurring : false;
        $productData['category_id'] = $product->category_id;

        // Add inventory tracking fields
        if ($variant) {
            $productData['track_inventory'] = $variant->track_inventory;
            $productData['stock'] = $variant->track_inventory ?
                ($variant->active_inventories_sum_available ?? 0) : null;
        } else {
            $productData['track_inventory'] = false;
            $productData['stock'] = null;
        }

        // Add compare_at_price field for discounted products
        $productData['compare_at_price'] = $variantData['compare_at_price'] ?? null;

        // Add variants for products with multiple variants
        if ($product->has_variant) {
            $variants = $product->variants()
                ->orderBy('id', 'asc')
                ->get();

            $productData['variants'] = $variants->map(function ($variant) {
                return $variant->only(['id', 'title', 'price', 'price_formatted', 'in_stock', 'thumbnail']);
            });
        }

        // Remove default variant from response as it's not needed
        unset($productData['default_variant']);

        return $productData;
    }
}
