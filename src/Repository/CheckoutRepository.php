<?php

namespace Coderstm\Repository;

use Coderstm\Models\Coupon;
use Coderstm\Models\Shop\Cart\LineItem;
use Coderstm\Models\Shop\Checkout;
use Coderstm\Models\Shop\Order\DiscountLine;
use Coderstm\Models\Subscription\Plan;
use Coderstm\Services\CouponService;
use Illuminate\Http\Request;

class CheckoutRepository extends BaseRepository
{
    public Checkout $checkout;

    public CouponService $couponService;

    protected $fillable = ['customer', 'line_items', 'coupon_code', 'billing_address', 'shipping_address', 'discount', 'tax_lines', 'collect_tax'];

    public function __construct(array $attributes = [], ?Checkout $checkout = null)
    {
        $this->couponService = app(CouponService::class);
        $this->checkout = $checkout ?? new Checkout(['type' => Checkout::TYPE_STANDARD, 'status' => Checkout::STATUS_DRAFT]);
        $attributes = $this->prepareDiscountAttributes($attributes);
        parent::__construct($attributes);
    }

    protected function prepareDiscountAttributes(array $attributes): array
    {
        if ($couponCode = $attributes['coupon_code'] ?? null) {
            $coupon = Coupon::findByCode($couponCode);
            if ($coupon && $coupon->exists) {
                $attributes['discount'] = DiscountLine::fromCoupon($coupon);
            }
        }
        if (array_key_exists('discount', $attributes)) {
            return $attributes;
        }

        return $this->applyAutoDiscount($attributes);
    }

    protected function applyAutoDiscount(array $attributes): array
    {
        if (! isset($attributes['line_items']) || ! is_array($attributes['line_items'])) {
            return $attributes;
        }
        $lineItems = $attributes['line_items'] ?? [];
        if (empty($lineItems)) {
            return $attributes;
        }
        if ($this->checkout->isSubscription() && $this->couponService->hasTrialPlans($lineItems)) {
            $attributes['discount'] = ['type' => 'percentage', 'value' => 100, 'description' => 'FREE TRIAL', 'coupon_code' => 'FREE_TRIAL'];
        } else {
            $result = $this->couponService->getBestAutoCouponData($lineItems, $this->checkout->isSubscription());
            $attributes = array_merge($attributes, $result);
        }

        return $attributes;
    }

    public static function fromCheckout(Checkout $checkout): self
    {
        $attributes = ['customer' => $checkout->customer?->toArray() ?? [], 'line_items' => $checkout->line_items?->map(function ($item) {
            $itemArray = $item->toArray();
            if (isset($itemArray['metadata']['plan_id'])) {
                $itemArray['plan_id'] = $itemArray['metadata']['plan_id'];
            }

            return $itemArray;
        })->toArray() ?? [], 'coupon_code' => $checkout->coupon_code, 'billing_address' => $checkout->billing_address, 'shipping_address' => $checkout->shipping_address, 'discount' => $checkout->discount?->toArray(), 'tax_lines' => $checkout->tax_lines?->toArray() ?? [], 'collect_tax' => true];

        return new self($attributes, $checkout);
    }

    public static function fromRequest(Request $request, ?Checkout $checkout = null): self
    {
        $checkout = $checkout ?? Checkout::getOrCreate();
        $customer = $request->customer ?? [];
        if ($checkout) {
            $customer = array_merge(['first_name' => $checkout->first_name, 'last_name' => $checkout->last_name, 'email' => $checkout->email, 'phone_number' => $checkout->phone_number], $customer);
        }
        if ($request->filled('line_items')) {
            $line_items = collect($request->line_items ?? [])->map(function ($product) {
                return LineItem::firstOrNew(['id' => $product['id'] ?? null], $product)->fill($product);
            });
            $checkout->setRelation('line_items', $line_items);
        }
        if ($request->filled('coupon_code')) {
            $checkout->coupon_code = $request->coupon_code;
        }
        $checkout->fill(['billing_address' => $request->billing_address ?? $checkout->billing_address, 'shipping_address' => $request->shipping_address ?? $checkout->shipping_address, 'note' => $request->note ?? $checkout->note, 'same_as_billing' => $request->same_as_billing ?? $checkout->same_as_billing, 'status' => $checkout->status ?? 'draft']);

        return self::fromCheckout($checkout);
    }

    public function hasTrialPlans(): bool
    {
        return $this->couponService->hasTrialPlans($this->line_items->toArray());
    }

    public function applyCoupon(string $couponCode): array
    {
        try {
            $lineItemsArray = $this->line_items->toArray();
            $result = $this->couponService->getCartLevelDiscountData($couponCode, $lineItemsArray, $this->checkout->isSubscription(), false);
            $this->discount = $result['discount_data'];
            $this->attributes['coupon_code'] = $result['coupon_code'];

            return ['application_level' => 'cart'];
        } catch (\Throwable $e) {
            $lineItemsArray = $this->line_items->toArray();
            $result = $this->couponService->applyCouponData($lineItemsArray, $this->checkout->isSubscription(), $couponCode);
            $this->discount = $result['discount'];
            $this->line_items = collect($result['line_items'])->map(function ($item) {
                return LineItem::firstOrNew(['id' => $item['id'] ?? null], $item)->fill($item);
            });

            return ['application_level' => $result['application_level']];
        }
    }

    public function removeAllLineItemDiscounts(): self
    {
        $this->discount = null;
        $this->attributes['discount'] = null;
        $this->attributes['coupon_code'] = null;
        if ($this->checkout) {
            $this->checkout->coupon_code = null;
            $this->unsetRelation('discount');
            $this->checkout->setRelation('discount', null);
        }
        $this->line_items = $this->line_items->map(function ($item) {
            $itemArray = $item->toArray();
            $itemArray['discount'] = null;

            return LineItem::firstOrNew(['id' => $itemArray['id'] ?? null], $itemArray)->fill($itemArray);
        });

        return $this;
    }

    public function recalculate(bool $withAutoCoupon = true): self
    {
        $this->useDefaultTax();
        if ($withAutoCoupon) {
            $this->applyAutoCouponsIfNeeded();
        }
        $this->syncRelationshipsToCheckout();

        return $this;
    }

    protected function syncRelationshipsToCheckout(): void
    {
        $this->syncAllRelationships();
    }

    public function saveToCheckout(): Checkout
    {
        $this->syncAllRelationships();
        $this->checkout->update(['sub_total' => $this->sub_total, 'tax_total' => $this->tax_total, 'shipping_total' => $this->shipping_total, 'discount_total' => $this->discount_total, 'grand_total' => $this->grand_total, 'coupon_code' => $this->coupon_code ?? $this->checkout->coupon_code]);

        return $this->checkout->refresh();
    }

    protected function syncAllRelationships(): void
    {
        if (! $this->checkout) {
            return;
        }
        $this->syncDiscount();
        $this->syncTaxLines();
        $this->syncLineItems();
    }

    protected function syncDiscount(): void
    {
        $discount = $this->discount;
        if ($discount && is_object($discount) && ! empty($discount->toArray())) {
            if ($this->checkout->discount) {
                $this->checkout->discount->update($discount->toArray());
            } else {
                $this->checkout->discount()->save($discount);
            }
        } else {
            $this->checkout->discount()?->delete();
            $this->checkout->setRelation('discount', null);
        }
    }

    protected function syncTaxLines(): void
    {
        if ($this->tax_lines && $this->tax_lines->isNotEmpty()) {
            $taxLines = $this->tax_lines->map(function ($taxLine) {
                return is_array($taxLine) ? $taxLine : $taxLine->toArray();
            });
            $this->checkout->syncTaxLines($taxLines);
        }
    }

    protected function syncLineItems(): void
    {
        if ($this->line_items) {
            $lineItems = collect($this->line_items)->map(function ($item) {
                $itemArray = is_array($item) ? $item : $item->toArray();
                if (isset($itemArray['plan_id'])) {
                    if (! isset($itemArray['metadata']) || ! is_array($itemArray['metadata'])) {
                        $itemArray['metadata'] = [];
                    }
                    $itemArray['metadata']['plan_id'] = $itemArray['plan_id'];
                }

                return $itemArray;
            });
            $this->checkout->syncLineItems($lineItems);
        }
    }

    public function calculate(bool $withAutoCoupon = true): self
    {
        return $this->recalculate($withAutoCoupon)->saveToDatabase();
    }

    public function saveToDatabase(): self
    {
        $this->checkout = $this->saveToCheckout();

        return $this;
    }

    protected function applyAutoCouponsIfNeeded(): void
    {
        if ($couponCode = $this->checkout->coupon_code) {
            $coupon = Coupon::findByCode($couponCode);
            if ($coupon && $coupon->exists) {
                $this->discount = DiscountLine::fromCoupon($coupon);

                return;
            }
        }
        if ($this->hasTrialPlans() && $this->checkout->isSubscription()) {
            $this->discount = ['type' => DiscountLine::TYPE_PERCENTAGE, 'value' => 100, 'description' => 'FREE TRIAL', 'coupon_code' => 'FREE_TRIAL'];

            return;
        }
        $lineItems = $this->line_items->toArray();
        $result = $this->couponService->getBestAutoCouponData($lineItems, $this->checkout->isSubscription());
        $this->discount = $result['discount'];
        $this->line_items = $result['line_items'];
        if (isset($result['discount']['coupon_code'])) {
            $this->coupon_code = $result['discount']['coupon_code'];
            $this->checkout->coupon_code = $result['discount']['coupon_code'];
        }
    }

    public function getCheckoutData(bool $useCalculated = true): array
    {
        if (! $useCalculated) {
            return array_merge($this->checkout->toArray(), ['line_items' => $this->getLineItems(), 'applied_coupons' => $this->getAppliedCoupons(), 'sub_total' => $this->checkout->sub_total ?? 0, 'tax_total' => $this->checkout->tax_total ?? 0, 'shipping_total' => $this->checkout->shipping_total ?? 0, 'discount_total' => $this->checkout->discount_total ?? 0, 'grand_total' => $this->checkout->grand_total ?? 0]);
        }

        return array_merge($this->checkout->toArray(), ['line_items' => $this->getLineItems(), 'applied_coupons' => $this->getAppliedCoupons(), 'sub_total' => $this->sub_total, 'tax_total' => $this->tax_total, 'shipping_total' => $this->shipping_total, 'discount_total' => $this->discount_total, 'grand_total' => $this->grand_total]);
    }

    protected function getLineItems(): array
    {
        return $this->line_items->map(function ($item) {
            $data = $item->toArray();
            $options = $item->variant?->getOptions() ?? [];
            if ($planId = $data['metadata']['plan_id'] ?? null) {
                $data['plan'] = Plan::find($planId);
            } elseif ($planId = $data['plan_id'] ?? null) {
                $data['plan'] = Plan::find($planId);
            }
            $data['options'] = $options;

            return $data;
        })->toArray();
    }

    public function getAppliedCoupons(): array
    {
        $coupons = [];
        if ($this->discount && $this->discount->coupon_code) {
            $coupons[] = ['level' => 'cart', 'code' => $this->discount->coupon_code, 'name' => $this->discount->description ?? ''];
        }
        if ($this->line_items) {
            foreach ($this->line_items as $index => $item) {
                if ($item->discount && $item->discount->coupon_code) {
                    $coupons[] = ['level' => 'line_item', 'line_item_index' => $index, 'code' => $item->discount->coupon_code, 'name' => $item->discount->description ?? ''];
                }
            }
        }

        return $coupons;
    }

    public function getCartItems()
    {
        return $this->getLineItems() ?? [];
    }
}
