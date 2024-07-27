<?php

namespace Coderstm\Commands;

use Coderstm\Coderstm;
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
            ->whereDoesntHave('logs', function ($q) {
                $q->where('type', 'expired-notification');
            });

        foreach ($subscriptions->cursor() as $subscription) {
            if ($user = $subscription->user) {
                try {
                    $user->notify(new SubscriptionExpiredNotification($user, $subscription));
                    admin_notify(new AdminsSubscriptionExpiredNotification($user, $subscription));
                    $subscription->logs()->create([
                        'type' => 'expired-notification',
                        'message' => 'Notification for expired subscriptions has been successfully sent.'
                    ]);
                } catch (\Exception $e) {
                    report($e);
                }
            }
        }

        $this->info('Expired subscriptions checked and notifications sent.');
    }
}
