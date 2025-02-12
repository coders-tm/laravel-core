<?php

namespace Coderstm\Commands;

use Coderstm\Coderstm;
use Coderstm\Models\Log;
use Coderstm\Notifications\Admins\SubscriptionExpiredNotification as AdminsSubscriptionExpiredNotification;
use Illuminate\Console\Command;
use Coderstm\Notifications\SubscriptionExpiredNotification;

class CheckExpiredSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coderstm:subscriptions-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expired subscriptions and send notifications';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $subscriptions = Coderstm::$subscriptionModel::where('expires_at', '<=', now())
            ->doesntHaveAction('expired-notification')
            ->with(['user']);

        foreach ($subscriptions->cursor() as $subscription) {
            if ($user = $subscription->user) {
                try {
                    event(new \Coderstm\Events\SubscriptionExpired($subscription));

                    $user->notify(new SubscriptionExpiredNotification($subscription));
                    admin_notify(new AdminsSubscriptionExpiredNotification($subscription));

                    $subscription->logs()->create([
                        'type' => 'expired-notification',
                        'message' => 'Notification for expired subscriptions has been successfully sent.'
                    ]);

                    $subscription->attachAction('expired-notification');
                } catch (\Exception $e) {
                    $subscription->logs()->create([
                        'type' => 'expired-notification',
                        'status' => Log::STATUS_ERROR,
                        'message' => $e->getMessage()
                    ]);
                }
            }
        }

        $this->info('Expired subscriptions checked and notifications sent.');
    }
}
