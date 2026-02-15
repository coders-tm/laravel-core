<?php

namespace Coderstm\Commands;

use Coderstm\Contracts\SubscriptionStatus;
use Coderstm\Models\Subscription;
use Illuminate\Console\Command;

class CheckGracePeriodSubscriptions extends Command
{
    protected $signature = 'coderstm:subscriptions-grace-check';

    protected $description = 'Check for grace period subscriptions and mark them as expired if grace period has ended';

    public function handle(): int
    {
        $this->info('Checking for subscriptions with expired grace periods...');
        $count = 0;
        $subscriptions = Subscription::query()->onGracePeriod()->where('ends_at', '<', now());
        foreach ($subscriptions->cursor() as $subscription) {
            $subscription->update(['status' => SubscriptionStatus::EXPIRED]);
            $count++;
        }
        if ($count === 0) {
            $this->info('No subscriptions found with expired grace periods.');

            return Command::SUCCESS;
        }
        $this->info("Marked {$count} subscription(s) as expired after grace period ended.");

        return Command::SUCCESS;
    }
}
