<?php

namespace Coderstm\Commands\Subscription;

use Coderstm\Coderstm;
use Coderstm\Events\ResetFeatureUsages;
use Coderstm\Models\Log;
use Illuminate\Console\Command;

class ResetUsages extends Command
{
    protected $signature = 'coderstm:subscriptions-reset-usages';

    protected $description = 'Reset the subscription usages for expired subscriptions and credit reset schedules';

    public function handle()
    {
        $subscriptions = Coderstm::$subscriptionModel::query()->active()->where('expires_at', '<=', now());
        foreach ($subscriptions->cursor() as $subscription) {
            $this->resetSubscriptionUsages($subscription);
        }
        $creditResetSubscriptions = Coderstm::$subscriptionModel::query()->active()->whereNotNull('credit_resets_at')->where('credit_resets_at', '<=', now())->where('expires_at', '>', now());
        foreach ($creditResetSubscriptions->cursor() as $subscription) {
            $this->resetSubscriptionUsages($subscription);
            $subscription->advanceCreditResetsAt()->save();
            $subscription->logs()->create(['type' => 'credit-reset', 'message' => 'Credit usage has been reset and next reset date advanced.']);
            $this->info("Credit usage of subscription #{$subscription->id} has been reset!");
        }
    }

    protected function resetSubscriptionUsages($subscription): void
    {
        try {
            event(new ResetFeatureUsages($subscription, $subscription->usagesToArray()));
            $subscription->resetUsages();
            $subscription->logs()->create(['type' => 'usages-reset', 'message' => 'Usages has been reset successfully!']);
            $this->info("Usages of subscription #{$subscription->id} has been reset!");
        } catch (\Throwable $e) {
            $message = "Usages of subscription #{$subscription->id} unable to reset! {$e->getMessage()}";
            $subscription->logs()->create(['type' => 'usages-reset', 'status' => Log::STATUS_ERROR, 'message' => $message]);
            $this->error($message);
        }
    }
}
