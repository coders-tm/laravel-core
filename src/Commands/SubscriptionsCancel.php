<?php

namespace Coderstm\Commands;

use Coderstm\Coderstm;
use Coderstm\Models\Log;
use Coderstm\Enum\AppStatus;
use Illuminate\Console\Command;

class SubscriptionsCancel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coderstm:subscriptions-cancel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel the subscription when it has cancels at';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $subscriptions = Coderstm::$subscriptionModel::query()
            ->active()
            ->where('cancels_at', '<=', now())
            ->doesntHaveAction('canceled');

        foreach ($subscriptions->cursor() as $subscription) {
            try {
                $user = $subscription->user();
                $subscription->cancelNow();

                event(new \Coderstm\Events\SubscriptionCancelled($subscription));

                $user->update([
                    'status' => AppStatus::DEACTIVE->value
                ]);

                $subscription->logs()->create([
                    'type' => 'canceled',
                    'message' => 'Subscription has been canceled successfully!'
                ]);

                $subscription->attachAction('canceled');

                $this->info("User #{$user->id} has been deactivated!");
            } catch (\Exception $e) {
                $message = "Subscription #{$subscription->id} unable to deactivated! {$e->getMessage()}";

                $subscription->logs()->create([
                    'type' => 'canceled',
                    'status' => Log::STATUS_ERROR,
                    'message' => $message
                ]);

                $this->error($message);
            }
        }
    }
}
