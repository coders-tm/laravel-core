<?php

namespace Coderstm\Commands;

use Coderstm\Coderstm;
use Coderstm\Notifications\Admins\SubscriptionCanceledNotification as AdminsSubscriptionCanceledNotification;
use Coderstm\Notifications\SubscriptionCanceledNotification;
use Illuminate\Console\Command;

class CheckCanceledSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coderstm:subscriptions-canceled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for canceled subscriptions and send notifications';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $subscriptions = Coderstm::$subscriptionModel::canceled()
            ->where('ends_at', '<=', now())
            ->whereDoesntHave('logs', function ($q) {
                $q->where('type', 'canceled-notification');
            });

        foreach ($subscriptions->cursor() as $subscription) {
            try {
                $user = $subscription->user;
                $user->notify(new SubscriptionCanceledNotification($user, $subscription));
                admin_notify(new AdminsSubscriptionCanceledNotification($user, $subscription));
                $subscription->logs()->create([
                    'type' => 'canceled-notification',
                    'message' => 'Notification for canceled subscriptions has been successfully sent.'
                ]);
            } catch (\Throwable $th) {
                report($th);
            }
        }


        $this->info('Expired subscriptions checked and notifications sent.');
    }
}
