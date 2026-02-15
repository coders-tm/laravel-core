<?php

namespace Coderstm\Services;

use Coderstm\Models\Coupon;
use Coderstm\Models\Shop\Order\DiscountLine;
use Coderstm\Models\Subscription\Plan;

class CouponService
{
    protected array $planCache = [];

    protected array $couponCache = [];

    protected array $couponRelationsCache = [];

    protected function getAutoApplicableCouponsWithRelations(string $couponType): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = 'auto_'.$couponType;
        if (! isset($this->couponRelationsCache[$cacheKey])) {
            $this->couponRelationsCache[$cacheKey] = Coupon::autoApplicable()->where('type', $couponType)->with(['plans', 'products'])->get();
        }

        return $this->couponRelationsCache[$cacheKey];
    }

    protected function getCouponsWithRelations(string $couponType): \Illuminate\Database\Eloquent\Collection
    {
        if (! isset($this->couponRelationsCache[$couponType])) {
            $this->couponRelationsCache[$couponType] = Coupon::onlyActive()->where('type', $couponType)->with(['plans', 'products'])->get();
        }

        return $this->couponRelationsCache[$couponType];
    }

    protected function cachePlans(array $planIds): void
    {
        $uncachedPlanIds = array_diff($planIds, array_keys($this->planCache));
        if (! empty($uncachedPlanIds)) {
            $plans = Plan::whereIn('id', $uncachedPlanIds)->get();
            foreach ($plans as $plan) {
                $this->planCache[$plan->id] = $plan;
            }
        }
    }

    protected function getCachedPlan(int $planId): ?Plan
    {
        return $this->planCache[$planId] ?? null;
    }

    protected function preloadPlansForLineItems(array $lineItems): void
    {
        $planIds = collect($lineItems)->pluck('plan_id')->filter()->unique()->toArray();
        if (! empty($planIds)) {
            $this->cachePlans($planIds);
        }
    }

    public function findSpecificAutoApplicableCouponForLineItem($planId, $productId, $basePrice, bool $isSubscription): ?Coupon
    {
        $couponType = $isSubscription ? Coupon::TYPE_PLAN : Coupon::TYPE_PRODUCT;
        $coupons = $this->getAutoApplicableCouponsWithRelations($couponType);
        $specificCoupons = $coupons->filter(function ($coupon) use ($planId, $productId, $isSubscription) {
            if ($isSubscription && $planId) {
                return $coupon->plans->contains('id', $planId);
            } elseif (! $isSubscription && $productId) {
                return $coupon->products->contains('id', $productId);
            }

            return false;
        });

        return $this->findBestCouponFromCollection($specificCoupons, $basePrice, $isSubscription, $planId, $productId);
    }

    public function findSpecificCouponForLineItem($planId, $productId, $basePrice, bool $isSubscription): ?Coupon
    {
        $couponType = $isSubscription ? Coupon::TYPE_PLAN : Coupon::TYPE_PRODUCT;
        $coupons = $this->getCouponsWithRelations($couponType);
        $specificCoupons = $coupons->filter(function ($coupon) use ($planId, $productId, $isSubscription) {
            if ($isSubscription && $planId) {
                return $coupon->plans->contains('id', $planId);
            } elseif (! $isSubscription && $productId) {
                return $coupon->products->contains('id', $productId);
            }

            return false;
        });

        return $this->findBestCouponFromCollection($specificCoupons, $basePrice, $isSubscription, $planId, $productId);
    }

    public function findGeneralAutoApplicableCouponForLineItem($basePrice, bool $isSubscription): ?Coupon
    {
        $couponType = $isSubscription ? Coupon::TYPE_PLAN : Coupon::TYPE_PRODUCT;
        $coupons = $this->getAutoApplicableCouponsWithRelations($couponType);
        $generalCoupons = $coupons->filter(function ($coupon) {
            return $coupon->plans->isEmpty() && $coupon->products->isEmpty();
        });

        return $this->findBestCouponFromCollection($generalCoupons, $basePrice);
    }

    public function findGeneralCouponForLineItem($basePrice, bool $isSubscription): ?Coupon
    {
        $couponType = $isSubscription ? Coupon::TYPE_PLAN : Coupon::TYPE_PRODUCT;
        $coupons = $this->getCouponsWithRelations($couponType);
        $generalCoupons = $coupons->filter(function ($coupon) {
            return $coupon->plans->isEmpty() && $coupon->products->isEmpty();
        });

        return $this->findBestCouponFromCollection($generalCoupons, $basePrice);
    }

    public function findBestCouponForLineItem($planId, $productId, $basePrice, bool $isSubscription): ?Coupon
    {
        $couponType = $isSubscription ? Coupon::TYPE_PLAN : Coupon::TYPE_PRODUCT;
        $coupons = $this->getCouponsWithRelations($couponType);
        $applicableCoupons = $coupons->filter(function ($coupon) use ($planId, $productId, $isSubscription) {
            if ($isSubscription && $planId) {
                return $coupon->plans->contains('id', $planId) || $coupon->plans->isEmpty() && $coupon->products->isEmpty();
            } elseif (! $isSubscription && $productId) {
                return $coupon->products->contains('id', $productId) || $coupon->plans->isEmpty() && $coupon->products->isEmpty();
            }

            return $coupon->plans->isEmpty() && $coupon->products->isEmpty();
        });

        return $this->findBestCouponFromCollection($applicableCoupons, $basePrice, $isSubscription, $planId, $productId);
    }

    public function findBestAutoApplicableCartLevelCoupon(array $lineItems, bool $isSubscription): ?Coupon
    {
        $couponType = $isSubscription ? Coupon::TYPE_PLAN : Coupon::TYPE_PRODUCT;
        $coupons = $this->getAutoApplicableCouponsWithRelations($couponType);
        $applicableCoupons = $coupons->filter(function ($coupon) use ($lineItems, $isSubscription) {
            foreach ($lineItems as $item) {
                $planId = $item['plan_id'] ?? null;
                $productId = $item['product_id'] ?? null;
                if (! $this->canApplyToItemCached($coupon, $isSubscription, $planId, $productId)) {
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

    public function findBestCartLevelCoupon(array $lineItems, bool $isSubscription): ?Coupon
    {
        $couponType = $isSubscription ? Coupon::TYPE_PLAN : Coupon::TYPE_PRODUCT;
        $coupons = $this->getCouponsWithRelations($couponType);
        $applicableCoupons = $coupons->filter(function ($coupon) use ($lineItems, $isSubscription) {
            foreach ($lineItems as $item) {
                $planId = $item['plan_id'] ?? null;
                $productId = $item['product_id'] ?? null;
                if (! $this->canApplyToItemCached($coupon, $isSubscription, $planId, $productId)) {
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

    protected function canApplyToItemCached(Coupon $coupon, bool $isSubscription, $planId = null, $productId = null): bool
    {
        $expectedCouponType = $isSubscription ? Coupon::TYPE_PLAN : Coupon::TYPE_PRODUCT;
        if ($coupon->type !== $expectedCouponType) {
            return false;
        }
        if ($isSubscription && $planId) {
            if ($coupon->plans->isEmpty()) {
                return true;
            }

            return $coupon->plans->contains('id', $planId);
        } elseif (! $isSubscription && $productId) {
            if ($coupon->products->isEmpty()) {
                return true;
            }

            return $coupon->products->contains('id', $productId);
        }

        return false;
    }

    public function canApplyToItem(Coupon $coupon, bool $isSubscription, $planId = null, $productId = null): bool
    {
        $expectedCouponType = $isSubscription ? Coupon::TYPE_PLAN : Coupon::TYPE_PRODUCT;
        if ($coupon->type !== $expectedCouponType) {
            return false;
        }
        if ($isSubscription && $planId) {
            return $coupon->canApplyToPlan($planId);
        } elseif (! $isSubscription && $productId) {
            return $coupon->canApplyToProduct($productId);
        }

        return false;
    }

    public function canApplyCartLevelCoupon(array $lineItems, bool $isSubscription, Coupon $coupon): bool
    {
        foreach ($lineItems as $item) {
            $planId = $item['plan_id'] ?? null;
            $productId = $item['product_id'] ?? null;
            if (! $this->canApplyToItem($coupon, $isSubscription, $planId, $productId)) {
                return false;
            }
        }

        return true;
    }

    public function getCartLevelDiscountData(string $couponCode, array $lineItems, bool $isSubscription, bool $autoApplied = false): array
    {
        $coupon = Coupon::where('promotion_code', $couponCode)->with(['plans', 'products'])->first();
        if (! $coupon) {
            throw new \InvalidArgumentException('Invalid coupon code');
        }
        $expectedCouponType = $isSubscription ? Coupon::TYPE_PLAN : Coupon::TYPE_PRODUCT;
        if ($coupon->type !== $expectedCouponType) {
            throw new \InvalidArgumentException('Coupon type does not match checkout type');
        }
        if (! $this->canApplyToAnyItem($lineItems, $isSubscription, $coupon)) {
            throw new \InvalidArgumentException('Coupon cannot be applied to items in cart');
        }

        return ['discount_data' => $this->createDiscountArray($coupon, $autoApplied), 'coupon_code' => $coupon->promotion_code];
    }

    public function getBestAutoCouponData(array $lineItems, bool $isSubscription): array
    {
        if (empty($lineItems)) {
            return ['discount' => null, 'line_items' => $lineItems];
        }
        if ($this->hasTrialPlans($lineItems)) {
            return ['discount' => null, 'line_items' => $lineItems];
        }
        $specificLineItemCoupons = [];
        $hasSpecificCoupons = false;
        foreach ($lineItems as $index => $item) {
            $specificCoupon = $this->findSpecificAutoApplicableCouponForLineItem($item['plan_id'] ?? null, $item['product_id'] ?? null, $item['price'], $isSubscription);
            if ($specificCoupon) {
                $specificLineItemCoupons[$index] = $specificCoupon;
                $hasSpecificCoupons = true;
            }
        }
        if ($hasSpecificCoupons) {
            $updatedLineItems = $this->applyMixedLineItemCoupons($lineItems, $specificLineItemCoupons, $isSubscription);

            return ['discount' => null, 'line_items' => $updatedLineItems];
        }
        $bestCartCoupon = $this->findBestAutoApplicableCartLevelCoupon($lineItems, $isSubscription);
        $cartLevelDiscount = 0;
        if ($bestCartCoupon) {
            $subtotal = collect($lineItems)->sum(function ($item) {
                return $item['price'] * $item['quantity'];
            });
            $cartLevelDiscount = $bestCartCoupon->getAmount($subtotal);
        }
        $generalLineItemCoupons = [];
        $generalDiscountTotal = 0;
        foreach ($lineItems as $index => $item) {
            $generalCoupon = $this->findGeneralAutoApplicableCouponForLineItem($item['price'], $isSubscription);
            if ($generalCoupon) {
                $discount = $generalCoupon->getAmount($item['price']) * $item['quantity'];
                $generalDiscountTotal += $discount;
                $generalLineItemCoupons[$index] = $generalCoupon;
            }
        }
        if ($cartLevelDiscount >= $generalDiscountTotal && $bestCartCoupon) {
            $discountData = $this->createDiscountArray($bestCartCoupon, true);
            $cleanLineItems = $this->removeAllLineItemDiscounts($lineItems);

            return ['discount' => $discountData, 'line_items' => $cleanLineItems];
        } elseif (! empty($generalLineItemCoupons)) {
            $updatedLineItems = $this->applySpecificLineItemCoupons($lineItems, $generalLineItemCoupons);

            return ['discount' => null, 'line_items' => $updatedLineItems];
        }

        return ['discount' => null, 'line_items' => $lineItems];
    }

    public function applyCouponData(array $lineItems, bool $isSubscription, string $couponCode): array
    {
        $coupon = Coupon::where('promotion_code', $couponCode)->with(['plans', 'products'])->first();
        if (! $coupon) {
            throw new \InvalidArgumentException('Invalid coupon code');
        }
        if ($this->hasTrialPlans($lineItems)) {
            throw new \InvalidArgumentException('Coupons cannot be applied when trial discounts are active');
        }
        $applicableLineItems = [];
        $isSpecificCoupon = $coupon->products->isNotEmpty() || $coupon->plans->isNotEmpty();
        if ($isSpecificCoupon) {
            foreach ($lineItems as $index => $item) {
                $planId = $item['plan_id'] ?? null;
                $productId = $item['product_id'] ?? null;
                if ($this->canApplyToItemCached($coupon, $isSubscription, $planId, $productId)) {
                    $applicableLineItems[$index] = $item;
                }
            }
            if (! empty($applicableLineItems)) {
                $updatedLineItems = $this->applyLineItemSpecificCoupon($lineItems, $coupon, $applicableLineItems);

                return ['application_level' => 'line_item', 'discount' => null, 'line_items' => $updatedLineItems];
            } else {
                throw new \InvalidArgumentException('Coupon cannot be applied to items in cart');
            }
        } else {
            if ($this->canApplyCartLevelCoupon($lineItems, $isSubscription, $coupon)) {
                $discountData = $this->createDiscountArray($coupon, false);
                $cleanLineItems = $this->removeAllLineItemDiscounts($lineItems);

                return ['application_level' => 'cart', 'discount' => $discountData, 'line_items' => $cleanLineItems];
            } else {
                throw new \InvalidArgumentException('Coupon cannot be applied to items in cart');
            }
        }
    }

    protected function applyLineItemSpecificCoupon(array $lineItems, Coupon $coupon, array $applicableLineItems): array
    {
        foreach ($lineItems as $index => &$item) {
            $item['discount'] = null;
        }
        foreach ($applicableLineItems as $index => $applicableItem) {
            if (isset($lineItems[$index])) {
                $lineItems[$index]['discount'] = $this->createDiscountArray($coupon, true);
            }
        }

        return $lineItems;
    }

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

    protected function applyMixedLineItemCoupons(array $lineItems, array $specificCoupons, bool $isSubscription): array
    {
        foreach ($lineItems as $index => &$item) {
            $coupon = null;
            if (isset($specificCoupons[$index])) {
                $coupon = $specificCoupons[$index];
            } else {
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

    protected function removeAllLineItemDiscounts(array $lineItems): array
    {
        foreach ($lineItems as &$item) {
            $item['discount'] = null;
        }

        return $lineItems;
    }

    protected function findBestCouponFromCollection($coupons, $basePrice, ?bool $isSubscription = null, $planId = null, $productId = null): ?Coupon
    {
        $bestCoupon = null;
        $bestDiscountAmount = 0;
        foreach ($coupons as $coupon) {
            $canApply = true;
            if ($isSubscription !== null && ($planId || $productId)) {
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

    public function hasTrialPlans(array $lineItems): bool
    {
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

    public function canApplyCoupon(array $lineItems, bool $isSubscription, string $couponCode): bool
    {
        try {
            $coupon = Coupon::where('promotion_code', $couponCode)->with(['plans', 'products'])->first();
            if (! $coupon) {
                return false;
            }
            if ($this->hasTrialPlans($lineItems)) {
                return false;
            }
            $expectedCouponType = $isSubscription ? Coupon::TYPE_PLAN : Coupon::TYPE_PRODUCT;
            if ($coupon->type !== $expectedCouponType) {
                return false;
            }
            $isSpecificCoupon = $coupon->products->isNotEmpty() || $coupon->plans->isNotEmpty();
            if ($isSpecificCoupon) {
                foreach ($lineItems as $item) {
                    $planId = $item['plan_id'] ?? null;
                    $productId = $item['product_id'] ?? null;
                    if ($this->canApplyToItemCached($coupon, $isSubscription, $planId, $productId)) {
                        return true;
                    }
                }

                return false;
            } else {
                return $this->canApplyCartLevelCoupon($lineItems, $isSubscription, $coupon);
            }
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getApplicableCoupons(array $lineItems, bool $isSubscription): array
    {
        $couponType = $isSubscription ? Coupon::TYPE_PLAN : Coupon::TYPE_PRODUCT;
        $allCoupons = $this->getCouponsWithRelations($couponType);
        $applicableCoupons = $allCoupons->filter(function ($coupon) use ($lineItems, $isSubscription) {
            return $this->canApplyCoupon($lineItems, $isSubscription, $coupon->promotion_code);
        });

        return $applicableCoupons->map(function ($coupon) {
            return ['id' => $coupon->id, 'name' => $coupon->name, 'promotion_code' => $coupon->promotion_code, 'discount_type' => $coupon->discount_type, 'value' => $coupon->value, 'description' => $coupon->name];
        })->toArray();
    }

    public function calculatePotentialDiscount(array $lineItems, bool $isSubscription, string $couponCode): array
    {
        try {
            $coupon = Coupon::where('promotion_code', $couponCode)->with(['plans', 'products'])->first();
            if (! $coupon || ! $this->canApplyCoupon($lineItems, $isSubscription, $couponCode)) {
                return ['can_apply' => false, 'discount_amount' => 0, 'message' => 'Coupon cannot be applied'];
            }
            if ($this->hasTrialPlans($lineItems)) {
                return ['can_apply' => false, 'discount_amount' => 0, 'message' => 'Coupons cannot be applied when free trial discounts are active'];
            }
            $totalDiscount = 0;
            $isSpecificCoupon = $coupon->products->isNotEmpty() || $coupon->plans->isNotEmpty();
            if ($isSpecificCoupon) {
                foreach ($lineItems as $item) {
                    $planId = $item['plan_id'] ?? null;
                    $productId = $item['product_id'] ?? null;
                    if ($this->canApplyToItemCached($coupon, $isSubscription, $planId, $productId)) {
                        $itemPrice = $item['price'] * $item['quantity'];
                        $totalDiscount += $coupon->getAmount($itemPrice);
                    }
                }
            } else {
                $subtotal = collect($lineItems)->sum(function ($item) {
                    return $item['price'] * $item['quantity'];
                });
                $totalDiscount = $coupon->getAmount($subtotal);
            }

            return ['can_apply' => true, 'discount_amount' => $totalDiscount, 'discount_type' => $coupon->discount_type, 'application_level' => $isSpecificCoupon ? 'line_item' : 'cart', 'message' => 'Coupon will save '.number_format($totalDiscount, 2)];
        } catch (\Throwable $e) {
            return ['can_apply' => false, 'discount_amount' => 0, 'message' => 'Error calculating discount'];
        }
    }

    protected function createDiscountArray(Coupon $coupon, bool $autoApplied): array
    {
        $discountType = match ($coupon->discount_type) {
            'percentage' => DiscountLine::TYPE_PERCENTAGE,
            'fixed' => DiscountLine::TYPE_FIXED_AMOUNT,
            'override' => DiscountLine::TYPE_PRICE_OVERRIDE,
            default => DiscountLine::TYPE_PERCENTAGE,
        };

        return ['type' => $discountType, 'value' => $coupon->value, 'description' => $coupon->name, 'coupon_id' => $coupon->id, 'coupon_code' => $coupon->promotion_code, 'auto_applied' => $autoApplied];
    }
}
