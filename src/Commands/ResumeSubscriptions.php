<?php

namespace Coderstm\Commands;

use Coderstm\Coderstm;
use Illuminate\Console\Command;

class ResumeSubscriptions extends Command
{
    protected $signature = 'coderstm:subscriptions-resume';

    protected $description = 'Resume frozen subscriptions that have reached their release date';

    public function handle()
    {
        $subscriptionModel = Coderstm::$subscriptionModel;
        $subscriptions = $subscriptionModel::dueForUnfreeze()->get();
        if ($subscriptions->isEmpty()) {
            $this->info('No subscriptions due for resume.');

            return Command::SUCCESS;
        }
        $this->info("Found {$subscriptions->count()} subscription(s) to resume...");
        $resumed = 0;
        $failed = 0;
        foreach ($subscriptions as $subscription) {
            try {
                $subscription->unfreeze();
                $resumed++;
                $this->line("✓ Resumed subscription #{$subscription->id} for user #{$subscription->user_id}");
            } catch (\Throwable $e) {
                $failed++;
                $this->error("✗ Failed to resume subscription #{$subscription->id}: {$e->getMessage()}");
            }
        }
        $this->newLine();
        $this->info("Completed: {$resumed} resumed, {$failed} failed.");

        return Command::SUCCESS;
    }
}
