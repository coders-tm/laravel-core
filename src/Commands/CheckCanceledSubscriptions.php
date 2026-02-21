<?php

namespace Coderstm\Commands;

use Coderstm\Coderstm;
use Coderstm\Contracts\SubscriptionStatus;
use Coderstm\Models\Log;
use Coderstm\Notifications\Admins\SubscriptionCanceledNotification as AdminsSubscriptionCanceledNotification;
use Coderstm\Notifications\SubscriptionCanceledNotification;
use Illuminate\Console\Command;

class CheckCanceledSubscriptions extends Command
{
    protected $signature = 'coderstm:subscriptions-canceled';

    protected $description = 'Check for canceled subscriptions and send notifications';

    public function handle()
    {
        $subscriptions = Coderstm::$subscriptionModel::query()->canceled()->where('expires_at', '<=', now())->doesntHaveAction('canceled-notification')->hasUser()->with('user');
        foreach ($subscriptions->cursor() as $subscription) {
            try {
                $subscription->attachAction('canceled-notification');
                $subscription->update(['status' => SubscriptionStatus::CANCELED]);
                if ($user = $subscription->user) {
                    event(new \Coderstm\Events\SubscriptionCancelled($subscription));
                    $user->notify(new SubscriptionCanceledNotification($subscription));
                    admin_notify(new AdminsSubscriptionCanceledNotification($subscription));
                    $subscription->logs()->create(['type' => 'canceled-notification', 'message' => 'Notification for canceled subscriptions has been successfully sent.']);
                }
            } catch (\Throwable $e) {
                $subscription->logs()->create(['type' => 'canceled-notification', 'status' => Log::STATUS_ERROR, 'message' => $e->getMessage()]);
            }
        }
        $this->info('Expired subscriptions checked and notifications sent.');
    }
}
