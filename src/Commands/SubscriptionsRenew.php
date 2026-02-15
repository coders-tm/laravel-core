<?php

namespace Coderstm\Commands;

use Coderstm\Coderstm;
use Coderstm\Models\Log;
use Illuminate\Console\Command;

class SubscriptionsRenew extends Command
{
    protected $signature = 'coderstm:subscriptions-renew';

    protected $description = 'Renew subscriptions and reset feature usages (credits)';

    public function handle()
    {
        $subscriptions = Coderstm::$subscriptionModel::query()->active()->hasUser()->where('expires_at', '<=', now());
        $renewedCount = 0;
        $errorCount = 0;
        foreach ($subscriptions->cursor() as $subscription) {
            try {
                $subscription->attachAction('renew');
                $usagesBeforeRenewal = $subscription->usagesToArray();
                $subscription->renew();
                event(new \Coderstm\Events\SubscriptionRenewed($subscription));
                event(new \Coderstm\Events\ResetFeatureUsages($subscription, $usagesBeforeRenewal));
                $cycleInfo = $subscription->total_cycles ? "{$subscription->current_cycle}/{$subscription->total_cycles}" : $subscription->current_cycle;
                $subscription->logs()->create(['type' => 'renew', 'message' => "Subscription renewed successfully! Cycle {$cycleInfo}. Credits reset."]);
                $this->info("Subscription #{$subscription->id} renewed! ({$cycleInfo}, Credits reset)");
                $renewedCount++;
            } catch (\Throwable $e) {
                $message = "Subscription #{$subscription->id} unable to renew! {$e->getMessage()}";
                $subscription->logs()->create(['type' => 'renew', 'status' => Log::STATUS_ERROR, 'message' => $message]);
                $this->error($message);
                $errorCount++;
            }
        }
        $this->info("\nRenewal complete: {$renewedCount} renewed, {$errorCount} errors");

        return 0;
    }
}
