<?php

namespace Coderstm\Actions\Subscription;

use Coderstm\Contracts\SubscriptionStatus;
use Coderstm\Events\SubscriptionRenewed;
use Coderstm\Models\Subscription;

class ProcessSubscriptionPayment
{
    /**
     * Mark subscription as paid.
     *
     * @param  Subscription  $subscription
     * @param  mixed  $paymentMethod
     * @return Subscription
     */
    public function pay($subscription, $paymentMethod, array $options = [])
    {
        if (empty($paymentMethod)) {
            throw new \InvalidArgumentException('Please provide a payment method.');
        }

        try {
            if ($subscription->hasIncompletePayment()) {
                if ($subscription->expired()) {
                    event(new SubscriptionRenewed($subscription));
                }

                $subscription->load('latestInvoice');

                $invoice = $subscription->latestInvoice ?? (
                    app(GenerateSubscriptionInvoice::class)->execute($subscription, true, true)
                );

                if ($invoice) {
                    $invoice->markAsPaid($paymentMethod, array_merge([
                        'note' => 'Marked the manual payment as received',
                    ], $options));
                }
            }
        } finally {
            $subscription->fill([
                'status' => SubscriptionStatus::ACTIVE,
                'ends_at' => null,
            ])->save();

            $subscription->syncUsages();

            if (! $subscription->credit_resets_at) {
                $subscription->advanceCreditResetsAt($subscription->starts_at ?? now())->save();
            }
        }

        return $subscription;
    }

    /**
     * Handle payment confirmation.
     *
     * @param  Subscription  $subscription
     * @param  mixed  $order
     * @return Subscription
     */
    public function paymentConfirmation($subscription, $order = null)
    {
        $subscription->fill([
            'status' => SubscriptionStatus::ACTIVE,
            'ends_at' => null,
        ]);

        if (! $subscription->credit_resets_at) {
            $subscription->advanceCreditResetsAt($subscription->starts_at ?? now());
        }

        $subscription->save();

        return $subscription;
    }

    /**
     * Handle payment failure.
     *
     * @param  Subscription  $subscription
     * @param  mixed  $order
     * @return Subscription
     */
    public function paymentFailed($subscription, $order = null)
    {
        if ($subscription->status === SubscriptionStatus::PENDING) {
            $subscription->fill([
                'status' => SubscriptionStatus::INCOMPLETE,
            ])->save();
        }

        return $subscription;
    }
}
