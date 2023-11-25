<?php

namespace Coderstm\Commands;

use Coderstm\Coderstm;
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
        Coderstm::$subscriptionModel::where('expires_at', '<=', now())
            ->whereDoesntHave('logs', function ($q) {
                $q->where('type', 'expired-notification');
            })->chunkById(100, function ($subscriptions) {
                foreach ($subscriptions as $subscription) {
                    $user = $subscription->user;
                    try {
                        $user->notify(new SubscriptionExpiredNotification($user, $subscription));
                        $subscription->logs()->create([
                            'type' => 'expired-notification',
                            'message' => 'Notification for expired subscriptions has been successfully sent.'
                        ]);
                    } catch (\Throwable $th) {
                        report($th);
                    }
                }
            });

        $this->info('Expired subscriptions checked and notifications sent.');
    }
}
