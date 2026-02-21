<?php

namespace Coderstm\Commands;

use Coderstm\Events\Checkout\Abandoned;
use Coderstm\Models\Shop\Checkout;
use Illuminate\Console\Command;

class ProcessAbandonedCheckouts extends Command
{
    protected $signature = 'shop:process-abandoned-checkouts {--hours=2}';

    protected $description = 'Process abandoned checkouts and send reminders.';

    public function handle()
    {
        $hours = (int) $this->option('hours') ?? config('coderstm.shop.abandoned_cart_hours', 2);
        $cutoff = now()->subHours($hours);
        $carts = Checkout::whereNull('abandoned_at')->where('started_at', '<=', $cutoff)->with('user')->whereHas('user', function ($q) {
            $q->whereNotNull('email');
        });
        $count = 0;
        foreach ($carts->cursor() as $cart) {
            $cart->abandoned_at = now();
            $cart->save();
            $count++;
            Abandoned::dispatch($cart);
        }
        $this->info('Abandoned carts processed: '.$count);
    }
}
