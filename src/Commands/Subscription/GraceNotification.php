<?php

namespace Coderstm\Commands\Subscription;

use Coderstm\Coderstm;
use Coderstm\Models\Log;
use Coderstm\Notifications\SubscriptionGraceNotification;
use Illuminate\Console\Command;

class GraceNotification extends Command
{
    protected $signature = 'coderstm:subscriptions-grace-notification';

    protected $description = 'Send daily grace period reminder notifications';

    public function handle()
    {
        $today = now()->format('Y-m-d');
        $actionName = "grace-notification-{$today}";

        $subscriptions = Coderstm::$subscriptionModel::query()
            ->active()
            ->onGracePeriod()
            ->hasUser()
            ->with(['user']);

        foreach ($subscriptions->cursor() as $subscription) {
            try {
                if ($user = $subscription->user) {
                    if (apply_filters('subscription.grace_notification.should_send', true, $user, $subscription)) {
                        $user->notify(new SubscriptionGraceNotification($subscription));

                        $subscription->logs()->create([
                            'type' => $actionName,
                            'message' => 'Daily grace period notification has been successfully sent.',
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                $subscription->logs()->create([
                    'type' => $actionName,
                    'status' => Log::STATUS_ERROR,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $this->info('Grace period notifications sent.');
    }
}
