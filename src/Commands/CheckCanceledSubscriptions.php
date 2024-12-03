<?php

namespace Coderstm\Commands;

use Coderstm\Coderstm;
use Coderstm\Models\Log;
use Illuminate\Console\Command;
use Coderstm\Notifications\SubscriptionCanceledNotification;
use Coderstm\Notifications\Admins\SubscriptionCanceledNotification as AdminsSubscriptionCanceledNotification;

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
        $subscriptions = Coderstm::$subscriptionModel::query()
            ->canceled()
            ->where('ends_at', '<=', now())
            ->whereDoesntHave('logs', function ($q) {
                $q->where('type', 'canceled-notification');
            })->with(['user']);

        foreach ($subscriptions->cursor() as $subscription) {
            try {
                if ($user = $subscription->user) {
                    $user->notify(new SubscriptionCanceledNotification($user, $subscription));
                    admin_notify(new AdminsSubscriptionCanceledNotification($user, $subscription));
                    $subscription->logs()->create([
                        'type' => 'canceled-notification',
                        'message' => 'Notification for canceled subscriptions has been successfully sent.'
                    ]);
                }
            } catch (\Exception $e) {
                $subscription->logs()->create([
                    'type' => 'canceled-notification',
                    'status' => Log::STATUS_ERROR,
                    'message' => $e->getMessage()
                ]);
            }
        }


        $this->info('Expired subscriptions checked and notifications sent.');
    }
}
