<?php

namespace Coderstm\Commands;

use Coderstm\Coderstm;
use Coderstm\Models\Log;
use Coderstm\Notifications\Admins\SubscriptionExpiredNotification as AdminsSubscriptionExpiredNotification;
use Coderstm\Notifications\SubscriptionExpiredNotification;
use Illuminate\Console\Command;

class CheckExpiredSubscriptions extends Command
{
    protected $signature = 'coderstm:subscriptions-expired';

    protected $description = 'Check for expired subscriptions and send notifications';

    public function handle()
    {
        $subscriptions = Coderstm::$subscriptionModel::where('expires_at', '<=', now())->doesntHaveAction('expired-notification')->hasUser()->with(['user']);
        foreach ($subscriptions->cursor() as $subscription) {
            try {
                $subscription->attachAction('expired-notification');
                if ($user = $subscription->user) {
                    event(new \Coderstm\Events\SubscriptionExpired($subscription));
                    $user->notify(new SubscriptionExpiredNotification($subscription));
                    admin_notify(new AdminsSubscriptionExpiredNotification($subscription));
                    $subscription->logs()->create(['type' => 'expired-notification', 'message' => 'Notification for expired subscriptions has been successfully sent.']);
                }
            } catch (\Throwable $e) {
                $subscription->logs()->create(['type' => 'expired-notification', 'status' => Log::STATUS_ERROR, 'message' => $e->getMessage()]);
            }
        }
        $this->info('Expired subscriptions checked and notifications sent.');
    }
}
