<?php

namespace Coderstm\Commands;

use Coderstm\Models\Subscription;
use Coderstm\Models\Subscription\SubscriptionFeature;
use Coderstm\Models\Subscription\Usage;
use Illuminate\Console\Command;

class MigrateSubscriptionFeatures extends Command
{
    protected $signature = 'subscription:migrate-features
                            {--force : Force migration even if subscription features already exist}
                            {--billing-only : Only migrate billing intervals, skip features}';

    protected $description = 'Migrate subscription features and billing intervals from plans to subscriptions';

    public function handle()
    {
        $force = $this->option('force');
        $billingOnly = $this->option('billing-only');
        if ($billingOnly) {
            $this->info('🚀 Starting billing intervals migration...');
        } else {
            $this->info('🚀 Starting subscription features and billing intervals migration...');
        }
        $this->newLine();
        if (! $billingOnly && ! $force && SubscriptionFeature::count() > 0) {
            $this->warn('⚠️  Some subscription features already exist. Use --force to override or migrate remaining subscriptions.');
        }
        $subscriptions = Subscription::with(['plan.features'])->get();
        if ($subscriptions->isEmpty()) {
            $this->warn('⚠️  No subscriptions found to migrate.');

            return 0;
        }
        $this->info("📊 Found {$subscriptions->count()} subscriptions to migrate");
        $this->newLine();
        $progressBar = $this->output->createProgressBar($subscriptions->count());
        $progressBar->start();
        $migratedCount = 0;
        $skippedCount = 0;
        $alreadyMigratedCount = 0;
        $errorCount = 0;
        $billingIntervalUpdatedCount = 0;
        foreach ($subscriptions as $subscription) {
            try {
                if (! $billingOnly) {
                    $result = $this->migrateSubscriptionFeatures($subscription);
                    if ($result === 'migrated') {
                        $migratedCount++;
                    } elseif ($result === 'already_migrated') {
                        $alreadyMigratedCount++;
                    } else {
                        $skippedCount++;
                    }
                }
                if ($this->migrateBillingIntervals($subscription)) {
                    $billingIntervalUpdatedCount++;
                }
            } catch (\Throwable $e) {
                $errorCount++;
                $this->error("❌ Error migrating subscription {$subscription->id}: ".$e->getMessage());
            }
            $progressBar->advance();
        }
        $progressBar->finish();
        $this->newLine(2);
        $this->info('📈 Migration Summary:');
        $summaryData = [];
        if (! $billingOnly) {
            $summaryData[] = ['✅ Features Migrated', $migratedCount];
            $summaryData[] = ['🔄 Already Migrated', $alreadyMigratedCount];
            $summaryData[] = ['⏭️  Skipped', $skippedCount];
        }
        $summaryData[] = ['📅 Billing Intervals Updated', $billingIntervalUpdatedCount];
        if ($errorCount > 0) {
            $summaryData[] = ['❌ Errors', $errorCount];
        }
        $this->table(['Status', 'Count'], $summaryData);
        $this->info('🎉 Migration completed successfully!');

        return 0;
    }

    protected function migrateSubscriptionFeatures(Subscription $subscription): string
    {
        if (! $subscription->plan) {
            $this->warn("⚠️  Subscription {$subscription->id} has no plan, skipping...");

            return 'skipped';
        }
        $force = $this->option('force');
        if (! $force && SubscriptionFeature::where('subscription_id', $subscription->id)->exists()) {
            $this->warn("⚠️  Subscription {$subscription->id} already has features migrated, skipping...");

            return 'already_migrated';
        }
        $planFeatures = $subscription->plan->features;
        if ($planFeatures->isEmpty()) {
            $this->warn("⚠️  Subscription {$subscription->id} has no plan features, skipping...");

            return 'skipped';
        }
        $subscriptionFeatures = [];
        foreach ($planFeatures as $planFeature) {
            $usage = Usage::where('subscription_id', $subscription->id)->where('slug', $planFeature->slug)->first();
            $used = $usage ? $usage->used : 0;
            $resetAt = $usage ? $usage->reset_at : null;
            $subscriptionFeatureData = ['subscription_id' => $subscription->id, 'slug' => $planFeature->slug, 'label' => $planFeature->label, 'type' => $planFeature->type, 'resetable' => $planFeature->resetable, 'value' => $planFeature->pivot->value, 'used' => $used, 'reset_at' => $resetAt];
            SubscriptionFeature::updateOrCreate(['subscription_id' => $subscription->id, 'slug' => $planFeature->slug], $subscriptionFeatureData);
            $subscriptionFeatures[] = $subscriptionFeatureData;
        }
        $this->line('✅ Migrated '.count($subscriptionFeatures)." features for subscription {$subscription->id}");

        return 'migrated';
    }

    protected function migrateBillingIntervals(Subscription $subscription): bool
    {
        if (! $subscription->plan) {
            return false;
        }
        $updated = false;
        if (is_null($subscription->billing_interval)) {
            $plan = $subscription->plan;
            $subscription->billing_interval = $plan->interval->value;
            $subscription->billing_interval_count = $plan->interval_count;
            $updated = true;
            if ($plan->is_contract && is_null($subscription->total_cycles)) {
                $subscription->total_cycles = $plan->contract_cycles;
                $updated = true;
            }
            if ($updated) {
                $subscription->save();
            }
        }

        return $updated;
    }

    protected function getMonthsForInterval(string $interval, int $count): float
    {
        $monthsMap = ['day' => 1 / 30, 'week' => 1 / 4, 'month' => 1, 'year' => 12];

        return ($monthsMap[$interval] ?? 1) * $count;
    }

    public function cleanupOldUsageData()
    {
        $this->info('🧹 Cleaning up old usage data...');
        $usageCount = Usage::count();
        if ($usageCount > 0) {
            $this->warn("Found {$usageCount} old usage records. These can be safely deleted after migration.");
            if ($this->confirm('Do you want to delete old usage records?')) {
                Usage::truncate();
                $this->info('✅ Old usage records deleted.');
            }
        } else {
            $this->info('✅ No old usage records found.');
        }
    }
}
