<?php

namespace Coderstm\Commands;

use Coderstm\Coderstm;
use Coderstm\Models\Log;
use Illuminate\Console\Command;

class SubscriptionsRenew extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coderstm:subscriptions-renew';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Renew the subscription when it is active';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $subscriptions = Coderstm::$subscriptionModel::query()
            ->active()
            ->where('expires_at', '<=', now());


        foreach ($subscriptions->cursor() as $subscription) {
            try {
                $subscription->renew();

                event(new \Coderstm\Events\SubscriptionRenewed($subscription));

                $subscription->logs()->create([
                    'type' => 'renew',
                    'message' => 'Subscription has been renewed successfully!'
                ]);

                $subscription->attachAction('renew');

                $this->info("Subscription #{$subscription->id} has been renewed!");
            } catch (\Exception $e) {
                $message = "Subscription #{$subscription->id} unable to renew! {$e->getMessage()}";

                $subscription->logs()->create([
                    'type' => 'renew',
                    'status' => Log::STATUS_ERROR,
                    'message' => $message
                ]);

                $this->error($message);

                report($e);
            }
        }
    }
}
