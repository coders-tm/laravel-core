<?php

namespace Coderstm\Commands;

use Coderstm\Coderstm;
use Coderstm\Models\Log;
use Illuminate\Console\Command;
use Coderstm\Events\ResetFeatureUsages;

class ResetSubscriptionsUsages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coderstm:reset-subscriptions-usages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rest the subscription usages when it is active';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $subscriptions = Coderstm::$subscriptionModel::query()
            ->active()
            ->whereHas('usages', function ($q) {
                $q->where('reset_at', '<=', now());
            });


        foreach ($subscriptions->cursor() as $subscription) {
            try {
                event(new ResetFeatureUsages($subscription, $subscription->usagesToArray()));

                $subscription->resetUsages();

                $subscription->logs()->create([
                    'type' => 'usages-reset',
                    'message' => 'Usages has been reset successfully!'
                ]);

                $this->info("Usages of subscription #{$subscription->id} has been reset!");
            } catch (\Exception $e) {
                $message = "Usages of subscription #{$subscription->id} unable to reset! {$e->getMessage()}";

                $subscription->logs()->create([
                    'type' => 'usages-reset',
                    'status' => Log::STATUS_ERROR,
                    'message' => $message
                ]);

                $this->error($message);
            }
        }
    }
}
