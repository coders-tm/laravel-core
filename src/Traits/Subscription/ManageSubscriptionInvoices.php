<?php

namespace Coderstm\Traits\Subscription;

use Carbon\Carbon;
use Coderstm\Coderstm;
use Coderstm\Contracts\SubscriptionStatus;
use Coderstm\Models\Shop\Order;
use Coderstm\Repository\InvoiceRepository;
use Coderstm\Services\Period;
use Coderstm\Services\Resource;

trait ManageSubscriptionInvoices
{
    public function latestInvoice()
    {
        return $this->morphOne(Coderstm::$orderModel, 'orderable')->orderBy('created_at', 'desc');
    }

    public function invoices()
    {
        return $this->morphMany(Coderstm::$orderModel, 'orderable');
    }

    public function cancelOpenInvoices(): self
    {
        $openInvoices = $this->invoices()->where('status', Order::STATUS_OPEN);
        foreach ($openInvoices->cursor() as $order) {
            $order->markAsCancelled();
        }

        return $this;
    }

    public function upcomingInvoice($start = false, $dateFrom = null): ?InvoiceRepository
    {
        $plan = $this->nextPlan ?? $this->plan;
        if (! $plan) {
            return null;
        }
        $period = new Period($plan->interval->value, (int) $plan->interval_count, $dateFrom ?? $this->dateFrom());
        $dueDate = $start ? $this->dateFrom() : $this->expires_at;
        $dueDate = $dueDate && $dueDate->gt(now()) ? $dueDate : $period->getEndDate();

        return new InvoiceRepository(['source' => 'Membership', 'customer_id' => $this->user?->id, 'orderable_id' => $this->id, 'orderable_type' => static::class, 'due_date' => $dueDate, 'billing_address' => $this->user?->address?->toArray(), 'collect_tax' => true, 'line_items' => $this->generateLineItems($plan, $period)]);
    }

    public function pay($paymentMethod, array $options = []): self
    {
        if (empty($paymentMethod)) {
            throw new \InvalidArgumentException('Please provide a payment method.');
        }
        try {
            if ($this->hasIncompletePayment()) {
                $this->sendRenewNotification();
                $invoice = $this->latestInvoice;
                $invoice->markAsPaid($paymentMethod, array_merge(['note' => 'Marked the manual payment as received'], $options));
            }
        } finally {
            $this->setPeriod()->fill(['status' => SubscriptionStatus::ACTIVE, 'ends_at' => null])->save();
            $this->syncUsages();
        }

        return $this;
    }

    public function paymentConfirmation($order = null): self
    {
        $this->sendRenewNotification();
        $this->fill(['status' => SubscriptionStatus::ACTIVE, 'ends_at' => null])->save();

        return $this;
    }

    public function paymentFailed($order = null): self
    {
        if ($this->status === SubscriptionStatus::PENDING) {
            $this->fill(['status' => SubscriptionStatus::INCOMPLETE])->save();
        }

        return $this;
    }

    protected function generateLineItems($plan, $period)
    {
        $fromDate = Carbon::parse($period->getStartDate())->format('M d, Y');
        $toDate = Carbon::parse($period->getEndDate())->format('M d, Y');
        $interval = $plan->interval->value;
        $amount = $plan->formatPrice();
        $plan->loadMissing(['product']);
        $product = $plan->product?->title ?? null;
        $title = "{$plan->label} (at {$amount} / {$interval})";
        if ($product) {
            $title = "{$product} - {$title}";
        }
        $lineItems = [['title' => $title, 'product_id' => $plan->product?->id, 'variant_id' => $plan->variant_id, 'metadata' => ['description' => "{$fromDate} - {$toDate}", 'plan_id' => $plan->id], 'price' => $plan->price, 'total' => $plan->price, 'quantity' => 1, 'options' => ['title' => $title], 'discount' => $this->discount()]];
        if ($this->shouldChargeSetupFee($plan)) {
            $setupFee = $this->getSetupFee($plan);
            if ($setupFee > 0) {
                $lineItems[] = ['title' => __('Admission Fee'), 'price' => $setupFee, 'total' => $setupFee, 'quantity' => 1, 'metadata' => ['type' => 'setup_fee']];
            }
        }

        return $lineItems;
    }

    protected function shouldChargeSetupFee($plan): bool
    {
        if ($plan->setup_fee === 0.0) {
            return false;
        }
        $hasOtherSubscriptions = \Coderstm\Models\Subscription::where('user_id', $this->user_id)->where('id', '!=', $this->id)->exists();
        if ($hasOtherSubscriptions) {
            return false;
        }

        return $this->invoices()->count() === 0;
    }

    protected function getSetupFee($plan): float
    {
        return $plan->setup_fee ?? config('coderstm.subscription.setup_fee', 0.0);
    }

    public function generateInvoice($start = false, $force = false)
    {
        $upcomingInvoice = $this->upcomingInvoice($start);
        if (! $upcomingInvoice) {
            return null;
        }
        if ($this->onTrial() && ! $force) {
            return null;
        }
        $order = Coderstm::$orderModel::modifyOrCreate(new Resource($upcomingInvoice->toArray()));
        if ($order->is_paid) {
            $this->status = SubscriptionStatus::ACTIVE;
        } else {
            $this->status = $start ? SubscriptionStatus::PENDING : SubscriptionStatus::ACTIVE;
        }
        $this->next_plan = null;
        $this->is_downgrade = false;
        $this->save();

        return $order;
    }
}
