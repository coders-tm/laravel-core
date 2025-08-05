<?php

namespace Coderstm\Services;

use Coderstm\Models\Coupon;
use Coderstm\Models\Subscription\Plan;
use Coderstm\Models\Shop\Order\DiscountLine;

class CouponService
{
    // Cache for plans and coupons to avoid repeated queries
    protected array $planCache = [];
    protected array $couponCache = [];
    protected array $couponRelationsCache = [];

    /**
     * Get coupons with relations cached by type
     */
    protected function getCouponsWithRelations(string $couponType): \Illuminate\Database\Eloquent\Collection
    {
        if (!isset($this->couponRelationsCache[$couponType])) {
            $this->couponRelationsCache[$couponType] = Coupon::onlyActive()
                ->where('type', $couponType)
                ->with(['plans', 'products'])
                ->get();
        }

        return $this->couponRelationsCache[$couponType];
    }

    /**
     * Batch fetch plans and cache them
     */
    protected function cachePlans(array $planIds): void
    {
        $uncachedPlanIds = array_diff($planIds, array_keys($this->planCache));

        if (!empty($uncachedPlanIds)) {
            $plans = Plan::whereIn('id', $uncachedPlanIds)->get();
            foreach ($plans as $plan) {
                $this->planCache[$plan->id] = $plan;
            }
        }
    }

    /**
     * Get cached plan or null
     */
    protected function getCachedPlan(int $planId): ?Plan
    {
        return $this->planCache[$planId] ?? null;
    }

    /**
     * Extract all plan IDs from line items and cache them
     */
    protected function preloadPlansForLineItems(array $lineItems): void
    {
        $planIds = collect($lineItems)
            ->pluck('plan_id')
            ->filter()
            ->unique()
            ->toArray();

        if (!empty($planIds)) {
            $this->cachePlans($planIds);
        }
    }
    /**
     * Find the best specific coupon for a line item (product or plan specific)
     */
    public function findSpecificCouponForLineItem($planId, $productId, $basePrice, bool $isSubscription): ?Coupon
    {
        $couponType = $isSubscription ? Coupon::TYPE_PLAN : Coupon::TYPE_PRODUCT;
        $coupons = $this->getCouponsWithRelations($couponType);

        $specificCoupons = $coupons->filter(function ($coupon) use ($planId, $productId, $isSubscription) {
            if ($isSubscription && $planId) {
                return $coupon->plans->contains('id', $planId);
            } elseif (!$isSubscription && $productId) {
                return $coupon->products->contains('id', $productId);
            }
            return false;
        });

        return $this->findBestCouponFromCollection($specificCoupons, $basePrice, $isSubscription, $planId, $productId);
    }

    /**
     * Find the best general coupon for a line item (no specific restrictions)
     */
    public function findGeneralCouponForLineItem($basePrice, bool $isSubscription): ?Coupon
    {
        $couponType = $isSubscription ? Coupon::TYPE_PLAN : Coupon::TYPE_PRODUCT;
        $coupons = $this->getCouponsWithRelations($couponType);

        $generalCoupons = $coupons->filter(function ($coupon) {
            return $coupon->plans->isEmpty() && $coupon->products->isEmpty();
        });

        return $this->findBestCouponFromCollection($generalCoupons, $basePrice);
    }

    /**
     * Find the best coupon for a specific line item (specific or general)
     */
    public function findBestCouponForLineItem($planId, $productId, $basePrice, bool $isSubscription): ?Coupon
    {
        $couponType = $isSubscription ? Coupon::TYPE_PLAN : Coupon::TYPE_PRODUCT;
        $coupons = $this->getCouponsWithRelations($couponType);

        $applicableCoupons = $coupons->filter(function ($coupon) use ($planId, $productId, $isSubscription) {
            if ($isSubscription && $planId) {
                // Specific plan coupon or general coupon
                return $coupon->plans->contains('id', $planId) ||
                    ($coupon->plans->isEmpty() && $coupon->products->isEmpty());
            } elseif (!$isSubscription && $productId) {
                // Specific product coupon or general coupon
                return $coupon->products->contains('id', $productId) ||
                    ($coupon->plans->isEmpty() && $coupon->products->isEmpty());
            }

            // General coupons only
            return $coupon->plans->isEmpty() && $coupon->products->isEmpty();
        });

        return $this->findBestCouponFromCollection($applicableCoupons, $basePrice, $isSubscription, $planId, $productId);
    }

    /**
     * Find the best cart-level coupon that can apply to the entire checkout
     */
    public function findBestCartLevelCoupon(array $lineItems, bool $isSubscription): ?Coupon
    {
        $couponType = $isSubscription ? Coupon::TYPE_PLAN : Coupon::TYPE_PRODUCT;
        $coupons = $this->getCouponsWithRelations($couponType);

        $applicableCoupons = $coupons->filter(function ($coupon) use ($lineItems, $isSubscription) {
            // Check if this coupon can apply to ALL items in the cart
            foreach ($lineItems as $item) {
                $planId = $item['plan_id'] ?? null;
                $productId = $item['product_id'] ?? null;

                if (!$this->canApplyToItemCached($coupon, $isSubscription, $planId, $productId)) {
                    return false;
                }
            }
            return true;
        });

        $subtotal = collect($lineItems)->sum(function ($item) {
            return $item['price'] * $item['quantity'];
        });

        return $this->findBestCouponFromCollection($applicableCoupons, $subtotal);
    }

    /**
     * Check if coupon can be applied to a specific item (using cached relations)
     */
    protected function canApplyToItemCached(Coupon $coupon, bool $isSubscription, $planId = null, $productId = null): bool
    {
        // Check coupon type matches checkout type
        $expectedCouponType = $isSubscription ? Coupon::TYPE_PLAN : Coupon::TYPE_PRODUCT;
        if ($coupon->type !== $expectedCouponType) {
            return false;
        }

        if ($isSubscription && $planId) {
            // Check if coupon has any plan restrictions
            if ($coupon->plans->isEmpty()) {
                return true; // General coupon, applies to all plans
            }
            return $coupon->plans->contains('id', $planId);
        } elseif (!$isSubscription && $productId) {
            // Check if coupon has any product restrictions
            if ($coupon->products->isEmpty()) {
                return true; // General coupon, applies to all products
            }
            return $coupon->products->contains('id', $productId);
        }

        return false;
    }

    /**
     * Check if coupon can be applied to a specific item
     */
    public function canApplyToItem(Coupon $coupon, bool $isSubscription, $planId = null, $productId = null): bool
    {
        // Check coupon type matches checkout type
        $expectedCouponType = $isSubscription ? Coupon::TYPE_PLAN : Coupon::TYPE_PRODUCT;
        if ($coupon->type !== $expectedCouponType) {
            return false;
        }

        if ($isSubscription && $planId) {
            return $coupon->canApplyToPlan($planId);
        } elseif (!$isSubscription && $productId) {
            return $coupon->canApplyToProduct($productId);
        }

        return false;
    }

    /**
     * Check if coupon can be applied at cart level (all items must be eligible)
     */
    public function canApplyCartLevelCoupon(array $lineItems, bool $isSubscription, Coupon $coupon): bool
    {
        foreach ($lineItems as $item) {
            $planId = $item['plan_id'] ?? null;
            $productId = $item['product_id'] ?? null;

            if (!$this->canApplyToItem($coupon, $isSubscription, $planId, $productId)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get cart-level discount data for a coupon
     */
    public function getCartLevelDiscountData(string $couponCode, array $lineItems, bool $isSubscription, bool $autoApplied = false): array
    {
        $coupon = Coupon::where('promotion_code', $couponCode)->with(['plans', 'products'])->first();

        if (!$coupon) {
            throw new \InvalidArgumentException('Invalid coupon code');
        }

        // Check if coupon type matches checkout type
        $expectedCouponType = $isSubscription ? Coupon::TYPE_PLAN : Coupon::TYPE_PRODUCT;
        if ($coupon->type !== $expectedCouponType) {
            throw new \InvalidArgumentException('Coupon type does not match checkout type');
        }

        // Check if coupon can be applied to any item in the cart
        if (!$this->canApplyToAnyItem($lineItems, $isSubscription, $coupon)) {
            throw new \InvalidArgumentException('Coupon cannot be applied to items in cart');
        }

        return [
            'discount_data' => $this->createDiscountArray($coupon, $autoApplied),
            'coupon_code' => $coupon->promotion_code,
        ];
    }

    /**
     * Apply the best coupon automatically (cart-level vs line-item level)
     * Returns array with discount and line_items data
     */
    public function getBestAutoCouponData(array $lineItems, bool $isSubscription): array
    {
        if (empty($lineItems)) {
            return ['discount' => null, 'line_items' => $lineItems];
        }

        // Check if any line item has a plan with free trial - skip auto coupons if so
        if ($this->hasTrialPlans($lineItems)) {
            return ['discount' => null, 'line_items' => $lineItems];
        }

        // Step 1: Find specific coupons for each line item
        $specificLineItemCoupons = [];
        $hasSpecificCoupons = false;

        foreach ($lineItems as $index => $item) {
            $specificCoupon = $this->findSpecificCouponForLineItem(
                $item['plan_id'] ?? null,
                $item['product_id'] ?? null,
                $item['price'],
                $isSubscription
            );

            if ($specificCoupon) {
                $specificLineItemCoupons[$index] = $specificCoupon;
                $hasSpecificCoupons = true;
            }
        }

        // Step 2: If we have specific coupons, use line-item approach
        if ($hasSpecificCoupons) {
            $updatedLineItems = $this->applyMixedLineItemCoupons($lineItems, $specificLineItemCoupons, $isSubscription);
            return ['discount' => null, 'line_items' => $updatedLineItems];
        }

        // Step 3: Compare cart-level vs line-item general coupons
        $bestCartCoupon = $this->findBestCartLevelCoupon($lineItems, $isSubscription);
        $cartLevelDiscount = 0;

        if ($bestCartCoupon) {
            $subtotal = collect($lineItems)->sum(function ($item) {
                return $item['price'] * $item['quantity'];
            });
            $cartLevelDiscount = $bestCartCoupon->getAmount($subtotal);
        }

        // Find best general line-item coupons
        $generalLineItemCoupons = [];
        $generalDiscountTotal = 0;

        foreach ($lineItems as $index => $item) {
            $generalCoupon = $this->findGeneralCouponForLineItem($item['price'], $isSubscription);

            if ($generalCoupon) {
                $discount = $generalCoupon->getAmount($item['price']) * $item['quantity'];
                $generalDiscountTotal += $discount;
                $generalLineItemCoupons[$index] = $generalCoupon;
            }
        }

        // Step 4: Choose best approach for general coupons
        if ($cartLevelDiscount >= $generalDiscountTotal && $bestCartCoupon) {
            $discountData = $this->createDiscountArray($bestCartCoupon, true);
            $cleanLineItems = $this->removeAllLineItemDiscounts($lineItems);
            return ['discount' => $discountData, 'line_items' => $cleanLineItems];
        } else if (!empty($generalLineItemCoupons)) {
            $updatedLineItems = $this->applySpecificLineItemCoupons($lineItems, $generalLineItemCoupons);
            return ['discount' => null, 'line_items' => $updatedLineItems];
        }

        return ['discount' => null, 'line_items' => $lineItems];
    }

    /**
     * Apply a coupon manually by code
     * Returns array with application level and updated data
     */
    public function applyCouponData(array $lineItems, bool $isSubscription, string $couponCode): array
    {
        $coupon = Coupon::where('promotion_code', $couponCode)->with(['plans', 'products'])->first();

        if (!$coupon) {
            throw new \InvalidArgumentException('Invalid coupon code');
        }

        // Check if has trial plans
        if ($this->hasTrialPlans($lineItems)) {
            throw new \InvalidArgumentException('Coupons cannot be applied when trial discounts are active');
        }

        $applicableLineItems = [];

        // Check if this is a product/plan specific coupon
        $isSpecificCoupon = $coupon->products->isNotEmpty() || $coupon->plans->isNotEmpty();

        if ($isSpecificCoupon) {
            // For specific coupons, check which line items are eligible
            foreach ($lineItems as $index => $item) {
                $planId = $item['plan_id'] ?? null;
                $productId = $item['product_id'] ?? null;

                if ($this->canApplyToItemCached($coupon, $isSubscription, $planId, $productId)) {
                    $applicableLineItems[$index] = $item;
                }
            }

            // Apply to specific line items if any are eligible
            if (!empty($applicableLineItems)) {
                $updatedLineItems = $this->applyLineItemSpecificCoupon($lineItems, $coupon, $applicableLineItems);
                return [
                    'application_level' => 'line_item',
                    'discount' => null,
                    'line_items' => $updatedLineItems
                ];
            } else {
                throw new \InvalidArgumentException('Coupon cannot be applied to items in cart');
            }
        } else {
            // For general coupons, apply at cart level if valid for all items
            if ($this->canApplyCartLevelCoupon($lineItems, $isSubscription, $coupon)) {
                $discountData = $this->createDiscountArray($coupon, false);
                $cleanLineItems = $this->removeAllLineItemDiscounts($lineItems);
                return [
                    'application_level' => 'cart',
                    'discount' => $discountData,
                    'line_items' => $cleanLineItems
                ];
            } else {
                throw new \InvalidArgumentException('Coupon cannot be applied to items in cart');
            }
        }
    }

    /**
     * Apply coupon to specific line items
     */
    protected function applyLineItemSpecificCoupon(array $lineItems, Coupon $coupon, array $applicableLineItems): array
    {
        // Clear all line item discounts first
        foreach ($lineItems as $index => &$item) {
            $item['discount'] = null;
        }

        // Apply coupon only to applicable line items
        foreach ($applicableLineItems as $index => $applicableItem) {
            if (isset($lineItems[$index])) {
                $lineItems[$index]['discount'] = $this->createDiscountArray($coupon, true);
            }
        }

        return $lineItems;
    }

    /**
     * Apply specific coupons to specific line items
     */
    protected function applySpecificLineItemCoupons(array $lineItems, array $lineItemCoupons): array
    {
        foreach ($lineItems as $index => &$item) {
            if (isset($lineItemCoupons[$index])) {
                $coupon = $lineItemCoupons[$index];
                $item['discount'] = $this->createDiscountArray($coupon, true);
            } else {
                $item['discount'] = null;
            }
        }

        return $lineItems;
    }

    /**
     * Apply mixed line item coupons (specific + general to fill gaps)
     */
    protected function applyMixedLineItemCoupons(array $lineItems, array $specificCoupons, bool $isSubscription): array
    {
        foreach ($lineItems as $index => &$item) {
            $coupon = null;

            // First priority: Use specific coupon if available
            if (isset($specificCoupons[$index])) {
                $coupon = $specificCoupons[$index];
            } else {
                // Second priority: Use best general coupon for items without specific coupons
                $coupon = $this->findGeneralCouponForLineItem($item['price'], $isSubscription);
            }

            if ($coupon) {
                $item['discount'] = $this->createDiscountArray($coupon, true);
            } else {
                $item['discount'] = null;
            }
        }

        return $lineItems;
    }

    /**
     * Remove all line item discounts
     */
    protected function removeAllLineItemDiscounts(array $lineItems): array
    {
        foreach ($lineItems as &$item) {
            $item['discount'] = null;
        }

        return $lineItems;
    }

    /**
     * Helper method to find the best coupon from a collection
     */
    protected function findBestCouponFromCollection($coupons, $basePrice, ?bool $isSubscription = null, $planId = null, $productId = null): ?Coupon
    {
        $bestCoupon = null;
        $bestDiscountAmount = 0;

        foreach ($coupons as $coupon) {
            $canApply = true;

            // If subscription info is provided, validate the coupon can apply
            if ($isSubscription !== null && ($planId || $productId)) {
                // Use cached method if coupon has relations loaded, otherwise fallback to original method
                if ($coupon->relationLoaded('plans') && $coupon->relationLoaded('products')) {
                    $canApply = $this->canApplyToItemCached($coupon, $isSubscription, $planId, $productId);
                } else {
                    $canApply = $this->canApplyToItem($coupon, $isSubscription, $planId, $productId);
                }
            }

            if ($canApply) {
                $discountAmount = $coupon->getAmount($basePrice);

                if ($discountAmount > $bestDiscountAmount) {
                    $bestDiscountAmount = $discountAmount;
                    $bestCoupon = $coupon;
                }
            }
        }

        return $bestCoupon;
    }

    /**
     * Check if line items have trial plans
     */
    public function hasTrialPlans(array $lineItems): bool
    {
        // Preload plans if not already cached
        $this->preloadPlansForLineItems($lineItems);

        foreach ($lineItems as $item) {
            if (isset($item['plan_id'])) {
                $plan = $this->getCachedPlan($item['plan_id']);
                if ($plan && $plan->hasTrial()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if coupon can apply to any item in cart
     */
    protected function canApplyToAnyItem(array $lineItems, bool $isSubscription, Coupon $coupon): bool
    {
        foreach ($lineItems as $item) {
            $planId = $item['plan_id'] ?? null;
            $productId = $item['product_id'] ?? null;

            if ($this->canApplyToItemCached($coupon, $isSubscription, $planId, $productId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a coupon can be applied to line items
     */
    public function canApplyCoupon(array $lineItems, bool $isSubscription, string $couponCode): bool
    {
        try {
            $coupon = Coupon::where('promotion_code', $couponCode)->with(['plans', 'products'])->first();

            if (!$coupon) {
                return false;
            }

            // Check if has trial plans
            if ($this->hasTrialPlans($lineItems)) {
                return false; // Cannot apply coupons when trial plans exist
            }

            // Check coupon type matches checkout type
            $expectedCouponType = $isSubscription ? Coupon::TYPE_PLAN : Coupon::TYPE_PRODUCT;
            if ($coupon->type !== $expectedCouponType) {
                return false;
            }

            // Check if specific coupon
            $isSpecificCoupon = $coupon->products->isNotEmpty() || $coupon->plans->isNotEmpty();

            if ($isSpecificCoupon) {
                // Check if any line item is eligible
                foreach ($lineItems as $item) {
                    $planId = $item['plan_id'] ?? null;
                    $productId = $item['product_id'] ?? null;

                    if ($this->canApplyToItemCached($coupon, $isSubscription, $planId, $productId)) {
                        return true;
                    }
                }
                return false;
            } else {
                // For general coupons, check if can apply to all items
                return $this->canApplyCartLevelCoupon($lineItems, $isSubscription, $coupon);
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get all applicable coupons for line items
     */
    public function getApplicableCoupons(array $lineItems, bool $isSubscription): array
    {
        $couponType = $isSubscription ? Coupon::TYPE_PLAN : Coupon::TYPE_PRODUCT;

        // Get all coupons with relations in one query
        $allCoupons = $this->getCouponsWithRelations($couponType);

        $applicableCoupons = $allCoupons->filter(function ($coupon) use ($lineItems, $isSubscription) {
            return $this->canApplyCoupon($lineItems, $isSubscription, $coupon->promotion_code);
        });

        return $applicableCoupons->map(function ($coupon) {
            return [
                'id' => $coupon->id,
                'name' => $coupon->name,
                'promotion_code' => $coupon->promotion_code,
                'discount_type' => $coupon->discount_type,
                'value' => $coupon->value,
                'description' => $coupon->name,
            ];
        })->toArray();
    }

    /**
     * Calculate potential discount for a coupon
     */
    public function calculatePotentialDiscount(array $lineItems, bool $isSubscription, string $couponCode): array
    {
        try {
            $coupon = Coupon::where('promotion_code', $couponCode)->with(['plans', 'products'])->first();

            if (!$coupon || !$this->canApplyCoupon($lineItems, $isSubscription, $couponCode)) {
                return [
                    'can_apply' => false,
                    'discount_amount' => 0,
                    'message' => 'Coupon cannot be applied'
                ];
            }

            // Check if has trial plans
            if ($this->hasTrialPlans($lineItems)) {
                return [
                    'can_apply' => false,
                    'discount_amount' => 0,
                    'message' => 'Coupons cannot be applied when free trial discounts are active'
                ];
            }

            $totalDiscount = 0;

            // Check if specific coupon
            $isSpecificCoupon = $coupon->products->isNotEmpty() || $coupon->plans->isNotEmpty();

            if ($isSpecificCoupon) {
                // Calculate discount for applicable line items
                foreach ($lineItems as $item) {
                    $planId = $item['plan_id'] ?? null;
                    $productId = $item['product_id'] ?? null;

                    if ($this->canApplyToItemCached($coupon, $isSubscription, $planId, $productId)) {
                        $itemPrice = $item['price'] * $item['quantity'];
                        $totalDiscount += $coupon->getAmount($itemPrice);
                    }
                }
            } else {
                // Calculate cart-level discount
                $subtotal = collect($lineItems)->sum(function ($item) {
                    return $item['price'] * $item['quantity'];
                });
                $totalDiscount = $coupon->getAmount($subtotal);
            }

            return [
                'can_apply' => true,
                'discount_amount' => $totalDiscount,
                'discount_type' => $coupon->discount_type,
                'application_level' => $isSpecificCoupon ? 'line_item' : 'cart',
                'message' => "Coupon will save " . number_format($totalDiscount, 2)
            ];
        } catch (\Exception $e) {
            return [
                'can_apply' => false,
                'discount_amount' => 0,
                'message' => 'Error calculating discount'
            ];
        }
    }

    /**
     * Create standardized discount array
     */
    protected function createDiscountArray(Coupon $coupon, bool $autoApplied): array
    {
        $discountType = match ($coupon->discount_type) {
            'percentage' => DiscountLine::TYPE_PERCENTAGE,
            'fixed' => DiscountLine::TYPE_FIXED_AMOUNT,
            'override' => DiscountLine::TYPE_PRICE_OVERRIDE,
            default => DiscountLine::TYPE_PERCENTAGE
        };

        return [
            'type' => $discountType,
            'value' => $coupon->value,
            'description' => $coupon->name,
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->promotion_code,
            'auto_applied' => $autoApplied,
        ];
    }
}
