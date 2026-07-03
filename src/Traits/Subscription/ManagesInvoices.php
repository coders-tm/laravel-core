<?php

namespace Coderstm\Traits\Subscription;

use Carbon\Carbon;
use Coderstm\Coderstm;
use Coderstm\Facades\Currency;
use Coderstm\Models\Shop\Order\DiscountLine;
use Coderstm\Models\Subscription;
use Coderstm\Repository\InvoiceRepository;
use Coderstm\Services\Period;

trait ManagesInvoices
{
    public function latestInvoice()
    {
        return $this->morphOne(Coderstm::$orderModel, 'orderable')
            ->orderBy('created_at', 'desc');
    }

    public function invoices()
    {
        return $this->morphMany(Coderstm::$orderModel, 'orderable');
    }

    public function upcomingInvoice($start = false, $dateFrom = null, $plan = null, ?string $interval = null, ?string $couponCode = null): ?InvoiceRepository
    {
        $plan = $plan ?? $this->nextPlan ?? $this->plan;

        if (is_numeric($plan)) {
            $plan = Coderstm::$planModel::find($plan);
        }

        if (! $plan) {
            return null;
        }

        $billingInterval = $interval ?? ($this->billing_interval ? (is_string($this->billing_interval) ? $this->billing_interval : $this->billing_interval->value) : $plan->interval->value);
        $billingIntervalCount = $this->billing_interval_count ?? $plan->interval_count;

        $period = new Period(
            $billingInterval,
            $billingIntervalCount,
            $dateFrom ?? $this->dateFrom()
        );

        $dueDate = $start ? $this->dateFrom() : $this->expires_at;
        $dueDate = $dueDate && $dueDate->gt(now()) ? $dueDate : $period->getEndDate();

        $price = $billingInterval === 'year'
            ? (float) ($plan->yearly_fee ?? $plan->price)
            : (float) ($plan->price ?? 0);

        $discount = null;
        if ($couponCode) {
            $couponModel = Coderstm::$couponModel;
            $coupon = $couponModel::findByCode($couponCode);
            if ($coupon && $coupon->canApplyToPlan($plan)) {
                $discountType = match ($coupon->discount_type) {
                    'percentage' => DiscountLine::TYPE_PERCENTAGE,
                    'fixed' => DiscountLine::TYPE_FIXED_AMOUNT,
                    'override' => DiscountLine::TYPE_PRICE_OVERRIDE,
                    default => DiscountLine::TYPE_PERCENTAGE
                };
                $discount = [
                    'type' => $discountType,
                    'value' => $coupon->value,
                    'description' => $coupon->name,
                    'coupon_id' => $coupon->id,
                    'coupon_code' => $coupon->promotion_code,
                    'auto_applied' => false,
                ];
            }
        } else {
            $discount = $this->discount();
        }

        $fromDate = Carbon::parse($period->getStartDate())->format('M d, Y');
        $toDate = Carbon::parse($period->getEndDate())->format('M d, Y');
        $amountStr = Currency::format($price);
        $title = "$plan->label (at $amountStr / $billingInterval)";

        $lineItems = [
            [
                'title' => $title,
                'metadata' => [
                    'description' => "$fromDate - $toDate",
                    'plan_id' => $plan->id,
                ],
                'price' => $price,
                'total' => $price,
                'quantity' => 1,
                'options' => ['title' => $title],
                'discount' => $discount,
            ],
        ];

        if ($this->shouldChargeSetupFee($plan)) {
            $setupFee = $this->getSetupFee($plan);
            if ($setupFee > 0) {
                $lineItems[] = [
                    'title' => __('Admission Fee'),
                    'price' => $setupFee,
                    'total' => $setupFee,
                    'quantity' => 1,
                    'metadata' => [
                        'type' => 'setup_fee',
                    ],
                ];
            }
        }

        return new InvoiceRepository([
            'source' => 'Membership',
            'customer_id' => $this->user?->id,
            'orderable_id' => $this->id,
            'orderable_type' => static::class,
            'due_date' => $dueDate,
            'billing_address' => $this->user?->billingAddress(),
            'collect_tax' => true,
            'line_items' => apply_filters('subscription.generate_line_items', $lineItems, $this),
        ]);
    }

    protected function shouldChargeSetupFee($plan): bool
    {
        if ($plan->setup_fee === 0.0) {
            return false;
        }

        $hasOtherSubscriptions = Subscription::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->exists();

        if ($hasOtherSubscriptions) {
            return false;
        }

        return $this->invoices()->count() === 0;
    }

    protected function getSetupFee($plan): float
    {
        return $plan->setup_fee ?? config('coderstm.subscription.setup_fee', 0.00);
    }
}
