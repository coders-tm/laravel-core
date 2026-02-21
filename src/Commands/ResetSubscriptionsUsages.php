<?php

namespace Coderstm\Commands;

use Coderstm\Coderstm;
use Coderstm\Events\ResetFeatureUsages;
use Coderstm\Models\Log;
use Illuminate\Console\Command;

class ResetSubscriptionsUsages extends Command
{
    protected $signature = 'coderstm:reset-subscriptions-usages';

    protected $description = 'Rest the subscription usages when it is active';

    public function handle()
    {
        $subscriptions = Coderstm::$subscriptionModel::query()->active()->whereHas('features', function ($q) {
            $q->where('reset_at', '<=', now());
        });
        foreach ($subscriptions->cursor() as $subscription) {
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
}
