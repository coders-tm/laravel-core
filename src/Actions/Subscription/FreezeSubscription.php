<?php

namespace Coderstm\Actions\Subscription;

use Carbon\Carbon;
use Coderstm\Contracts\SubscriptionStatus;
use Coderstm\Enum\LogType;
use Coderstm\Models\Subscription;

class FreezeSubscription
{
    /**
     * Freeze subscription.
     *
     * @param Subscription $subscription
     * @param Carbon|null $releaseAt
     * @param string|null $reason
     * @param float|null $fee
     * @return Subscription
     */
    public function execute($subscription, ?Carbon $releaseAt = null, ?string $reason = null, ?float $fee = null)
    {
        $freezeDays = $releaseAt ? now()->diffInDays($releaseAt) : 0;

        if (! $subscription->canFreeze($freezeDays)) {
            throw new \LogicException('Subscription cannot be frozen at this time.');
        }

        $freezeFee = $fee ?? $subscription->plan->getFreezeFee();

        $subscription->fill([
            'status' => SubscriptionStatus::PAUSED,
            'frozen_at' => now(),
            'release_at' => $releaseAt,
        ])->save();

        if ($freezeFee > 0) {
            $this->generateFreezeInvoice($subscription, $freezeFee, $releaseAt);
        }

        $logMessage = 'Subscription frozen';
        if ($reason) {
            $logMessage .= ": {$reason}";
        }
        if ($releaseAt) {
            $logMessage .= " (until {$releaseAt->format('Y-m-d')})";
        }

        $subscription->logs()->create([
            'type' => LogType::UPDATED,
            'message' => $logMessage,
        ]);

        return $subscription;
    }

    /**
     * Generate freeze invoice.
     *
     * @param Subscription $subscription
     * @param float $fee
     * @param Carbon|null $releaseAt
     * @return mixed
     */
    protected function generateFreezeInvoice($subscription, float $fee, ?Carbon $releaseAt = null)
    {
        $period = $releaseAt ? now()->diffInDays($releaseAt).' days' : 'indefinite';

        return $subscription->invoices()->create([
            'user_id' => $subscription->user_id,
            'status' => 'open',
            'description' => "Subscription freeze fee ({$period})",
            'sub_total' => $fee,
            'tax' => 0,
            'total' => $fee,
            'grand_total' => $fee,
            'lines' => [
                [
                    'description' => "Freeze Fee - {$subscription->plan->label}",
                    'quantity' => 1,
                    'unit_amount' => $fee,
                    'amount' => $fee,
                ],
            ],
        ]);
    }
}
