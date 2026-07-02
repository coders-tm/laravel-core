<?php

namespace Coderstm\Commands\Subscription;

use Coderstm\Coderstm;
use Coderstm\Models\Log;
use Coderstm\Notifications\SubscriptionExpiringNotification;
use Illuminate\Console\Command;

class ExpiringSoon extends Command
{
    protected $signature = 'coderstm:subscriptions-expiring-soon';

    protected $description = 'Check for subscriptions expiring soon and send reminder notifications';

    protected array $intervals = [['days' => 7, 'action' => 'expiring-7-days-notification'], ['days' => 2, 'action' => 'expiring-2-days-notification'], ['days' => 1, 'action' => 'expiring-1-day-notification']];

    public function handle()
    {
        $templateType = 'user:subscription-expiring-x-day';
        foreach ($this->intervals as $interval) {
            $targetDate = now()->addDays($interval['days'])->startOfDay();
            $subscriptions = Coderstm::$subscriptionModel::query()->active()->whereDate('expires_at', '=', $targetDate->toDateString())->hasUser()->with(['user']);
            foreach ($subscriptions->cursor() as $subscription) {
                try {
                    if ($user = $subscription->user) {
                        if (apply_filters('subscription.expiring_soon.should_send', true, $user, $subscription, $interval['days'])) {
                            $user->notify(new SubscriptionExpiringNotification($subscription, $templateType, $interval['days']));
                            $subscription->logs()->create(['type' => $interval['action'], 'message' => "Notification for subscription expiring in {$interval['days']} day(s) has been successfully sent."]);
                        }
                    }
                } catch (\Throwable $e) {
                    $subscription->logs()->create(['type' => $interval['action'], 'status' => Log::STATUS_ERROR, 'message' => $e->getMessage()]);
                }
            }
        }
        $this->info('Expiring soon subscriptions checked and notifications sent.');
    }
}
