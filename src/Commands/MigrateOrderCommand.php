<?php

namespace Coderstm\Commands;

use Coderstm\Models\Shop\Order;
use Illuminate\Console\Command;

class MigrateOrderCommand extends Command
{
    protected $signature = 'coderstm:migrate-orders
                            {--chunk=100 : Number of orders to process at once}';

    protected $description = 'Migrate existing orders to populate paid_total and refund_total columns';

    public function handle(): int
    {
        $this->info('Starting order totals migration...');
        $chunkSize = (int) $this->option('chunk');
        $totalOrders = Order::withTrashed()->count();
        if ($totalOrders === 0) {
            $this->info('No orders found to migrate.');

            return Command::SUCCESS;
        }
        $this->info("Found {$totalOrders} orders to migrate.");
        $bar = $this->output->createProgressBar($totalOrders);
        $bar->start();
        $updated = 0;
        $errors = 0;
        Order::withTrashed()->withSum('payments as calculated_paid_total', 'amount')->withSum('refunds as calculated_refund_total', 'amount')->chunk($chunkSize, function ($orders) use (&$updated, &$errors, $bar) {
            foreach ($orders as $order) {
                try {
                    $paidTotal = $order->calculated_paid_total ?? 0;
                    $refundTotal = $order->calculated_refund_total ?? 0;
                    if ($order->paid_total != $paidTotal || $order->refund_total != $refundTotal) {
                        $order->updateQuietly(['paid_total' => $paidTotal, 'refund_total' => $refundTotal]);
                        $updated++;
                    }
                } catch (\Throwable $e) {
                    $errors++;
                    $this->error("\nError updating order {$order->id}: ".$e->getMessage());
                }
                $bar->advance();
            }
        });
        $bar->finish();
        $this->newLine(2);
        $this->info("Successfully updated {$updated} orders.");
        if ($errors > 0) {
            $this->warn("Encountered {$errors} errors during migration.");
        }
        $this->newLine();
        $this->info('Order totals migration completed!');

        return Command::SUCCESS;
    }
}
