<?php

namespace Coderstm\Commands;

use Coderstm\Coderstm;
use Illuminate\Console\Command;
use Coderstm\Notifications\SubscriptionExpiredNotification;

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
        $canceledSubscriptions = Coderstm::$subscriptionModel::canceled()->where('ends_at', '<=', now())
            ->whereDoesntHave('logs', function ($q) {
                $q->where('type', 'canceled-notification');
            })->get();

        foreach ($canceledSubscriptions as $subscription) {
            $user = $subscription->user;
            try {
                $user->notify(new SubscriptionExpiredNotification($user, $subscription));
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
