<?php

namespace Coderstm\Traits\Subscription;

use Carbon\Carbon;
use Coderstm\Contracts\SubscriptionStatus;
use Coderstm\Models\Shop\Order;
use Coderstm\Services\Period;
use Coderstm\Services\Resource;
use Coderstm\Repository\InvoiceRepository;

trait HandlesSubscriptionInvoices
{
    /**
     * Get the latest invoice associated with the Subscription
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function latestInvoice()
    {
        return $this->morphOne(config('coderstm.models.order', Order::class), 'orderable')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get all invoices associated with the Subscription
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function invoices()
    {
        return $this->morphMany(config('coderstm.models.order', Order::class), 'orderable');
    }

    /**
     * Get the upcoming invoice for the subscription.
     *
     * @param bool $start Whether to use the start date as due date
     * @param Carbon|null $dateFrom The date from which to calculate the period
     * @return \Coderstm\Repository\InvoiceRepository|null
     */
    public function upcomingInvoice($start = false, $dateFrom = null): ?InvoiceRepository
    {
        $plan = $this->nextPlan ?? $this->plan;

        if (!$plan) {
            return null;
        }

        $period = new Period(
            $plan->interval->value,
            (int) $plan->interval_count,
            $dateFrom ?? $this->dateFrom()
        );

        return new InvoiceRepository([
            'source' => 'Membership',
            'customer_id' => $this->user->id,
            'orderable_id' => $this->id,
            'orderable_type' => static::class,
            'due_date' => $start ? $this->dateFrom() : $period->getEndDate(),
            'billing_address' => $this->user->address?->toArray(),
            'currency' => config('cashier.currency'),
            'collect_tax' => true,
            'line_items' => $this->generateLineItems($plan, $period),
        ]);
    }

    /**
     * Process payment for the subscription.
     *
     * @param mixed $paymentMethod The payment method to use
     * @param array $options Additional options for processing payment
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function pay($paymentMethod, array $options = [])
    {
        if (empty($paymentMethod)) {
            throw new \InvalidArgumentException('Please provide a payment method.');
        }

        try {
            if ($this->pastDue() || $this->hasIncompletePayment() || $this->isPending()) {
                $this->sendRenewNotification();
                $invoice = $this->latestInvoice;
                $invoice->markAsPaid($paymentMethod, [
                    'note' => 'Marked the manual payment as received',
                ] + $options);
            }
        } finally {
            $this->setPeriod()->fill([
                'status' => SubscriptionStatus::ACTIVE,
            ])->save();

            $this->syncUsages();
        }

        return $this;
    }

    /**
     * Handle payment confirmation callback.
     *
     * @param Order|null $order The order being confirmed
     * @return $this
     */
    public function paymentConfirmation(?Order $order = null)
    {
        $this->sendRenewNotification();

        // making subscription status as active
        $this->setPeriod()->fill([
            'status' => SubscriptionStatus::ACTIVE,
        ])->save();

        return $this;
    }

    /**
     * Handle payment failure callback.
     *
     * @param Order|null $order The failed order
     * @return $this
     */
    public function paymentFailed(?Order $order = null)
    {
        // Set subscription status to incomplete due to payment failure
        $this->fill([
            'status' => SubscriptionStatus::INCOMPLETE,
        ])->save();

        // Notify user about payment failure
        // $this->sendPaymentFailedNotification();

        return $this;
    }

    /**
     * Generate invoice items for a plan and period.
     *
     * @param mixed $plan The subscription plan
     * @param Period $period The billing period
     * @return array
     */
    protected function generateLineItems($plan, $period)
    {
        $fromDate = Carbon::parse($period->getStartDate())->format('M d, Y');
        $toDate = Carbon::parse($period->getEndDate())->format('M d, Y');
        $interval = $plan->interval->value;
        $amount = $plan->formatPrice();
        $title = "$plan->label  (at $amount / $interval)";

        return [
            [
                'title' => $title,
                'description' => "$fromDate - $toDate",
                'plan_id' => $plan->id,
                'price' => $plan->price,
                'total' => $plan->price,
                'quantity' => 1,
                'options' => ['title' => $title],
                'discount' => $this->discount(),
            ]
        ];
    }

    /**
     * Generate a new invoice for the subscription.
     *
     * @param bool $start Whether this is a start invoice
     * @return Order|null
     */
    protected function generateInvoice($start = false): ?Order
    {
        $order = Order::modifyOrCreate(new Resource($this->upcomingInvoice($start)->toArray()));

        if ($order->is_paid) {
            $this->status = SubscriptionStatus::ACTIVE;
        } else {
            $this->status = $start ? SubscriptionStatus::PENDING : SubscriptionStatus::PAST_DUE;
        }

        $this->next_plan = null;
        $this->is_downgrade = false;

        $this->save();

        return $order;
    }
}
