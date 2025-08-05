<?php

namespace Coderstm\Commands;

use Illuminate\Console\Command;
use Coderstm\Models\Shop\Checkout;
use Coderstm\Notifications\AbandonedCartReminder;

class DetectAbandonedCarts extends Command
{
    protected $signature = 'shop:detect-abandoned-carts {--hours=2}';
    protected $description = 'Detect abandoned carts and send reminders.';

    public function handle()
    {
        $hours = (int) $this->option('hours');
        $cutoff = now()->subHours($hours);

        $carts = Checkout::whereNull('abandoned_at')
            ->where(function ($q) use ($cutoff) {
                $q->where('updated_at', '<', $cutoff)
                    ->orWhere('created_at', '<', $cutoff);
            })
            ->with('user')
            ->get();

        foreach ($carts as $cart) {
            $cart->abandoned_at = now();
            $cart->save();
            // Send notification if user has email
            if ($cart->user && $cart->user->email) {
                $cart->user->notify(new AbandonedCartReminder($cart));
            }
        }

        $this->info("Abandoned carts processed: " . $carts->count());
    }
}
