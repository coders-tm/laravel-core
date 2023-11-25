<?php

namespace Coderstm\Commands;

use Coderstm\Enum\AppStatus;
use Illuminate\Console\Command;
use Coderstm\Models\Cashier\Subscription;

class SubscriptionsCancel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coderstm:subscriptions-cancel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel the subscription when it has cancels at';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Subscription::query()
            ->active()
            ->where('cancels_at', '<=', now())
            ->whereDoesntHave('logs', function ($query) {
                $query->where('type', 'canceled');
            })->chunkById(100, function ($subscriptions) {
                foreach ($subscriptions as $subscription) {
                    try {
                        $user = $subscription->user();
                        $subscription->cancelNow();
                        $user->update([
                            'status' => AppStatus::DEACTIVE->value
                        ]);
                        $subscription->logs()->create([
                            'type' => 'canceled',
                            'message' => 'Subscription has been canceled successfully!'
                        ]);
                        $this->info("User #{$user->id} has been deactivated!");
                    } catch (\Exception $ex) {
                        report($ex);
                        $this->error("User #{$user->id} unable to deactivated! {$ex->getMessage()}");
                    }
                }
            });
    }
}
