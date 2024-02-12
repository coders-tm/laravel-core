<?php

namespace Coderstm\Commands;

use Illuminate\Console\Command;
use Coderstm\Models\Cashier\Subscription;
use Coderstm\Models\Cashier\Invoice;

class SubscriptionsInvoice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coderstm:subscriptions-invoices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync invoices from stripe for current subscriptions';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Subscription::query()->whereRaw('LENGTH(stripe_id) = ?', [28])->each(function ($subscription) {
            try {
                foreach ($subscription->invoicesIncludingPending() as $invoice) {
                    Invoice::createFromStripe($invoice, [
                        'subscription_id' => $subscription->id
                    ]);
                }
                $this->info("[Subscription #{$subscription->id}]: Invoices has been synced!");
            } catch (\Exception $ex) {
                report($ex);
                $this->error("[Subscription #{$subscription->id}]: {$ex->getMessage()}");
            }
        });
    }
}
