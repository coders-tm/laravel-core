<?php

namespace Coderstm\Commands;

use Illuminate\Console\Command;
use Coderstm\Models\Shop\Checkout;
use Illuminate\Support\Facades\Log;

class ProcessAbandonedCheckouts extends Command
{
    protected $signature = 'checkout:process-abandoned {--dry-run : Show what would be processed without making changes}';
    protected $description = 'Process abandoned checkouts and send recovery emails';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        $this->info('Processing abandoned checkouts...');

        // Mark checkouts as abandoned
        $abandonedCheckouts = $this->markAbandonedCheckouts($isDryRun);

        // Send recovery emails
        $recoveryEmails = $this->sendRecoveryEmails($isDryRun);

        $this->info("Processed {$abandonedCheckouts} abandoned checkouts");
        $this->info("Sent {$recoveryEmails} recovery emails");

        if ($isDryRun) {
            $this->warn('This was a dry run. No changes were made.');
        }
    }

    protected function markAbandonedCheckouts($isDryRun = false): int
    {
        $query = Checkout::whereIn('status', ['draft', 'pending'])
            ->where('started_at', '<', now()->subHours(12))
            ->whereNull('abandoned_at');

        $count = $query->count();

        if ($count > 0) {
            $this->info("Found {$count} checkouts to mark as abandoned:");

            $checkouts = $query->get();
            foreach ($checkouts as $checkout) {
                $this->line("- Checkout {$checkout->token} ({$checkout->email})");

                if (!$isDryRun) {
                    $checkout->markAsAbandoned();
                }
            }
        }

        return $count;
    }

    protected function sendRecoveryEmails($isDryRun = false): int
    {
        $checkouts = Checkout::recoveryEligible()->get();
        $count = 0;

        foreach ($checkouts as $checkout) {
            try {
                $this->info("Sending recovery email to: {$checkout->email} (Checkout: {$checkout->token})");

                if (!$isDryRun) {
                    $this->sendRecoveryEmail($checkout);
                    $checkout->update(['recovery_email_sent_at' => now()]);
                }

                $count++;
            } catch (\Exception $e) {
                $this->error("Failed to send recovery email for checkout {$checkout->token}: {$e->getMessage()}");
                Log::error("Abandoned checkout recovery email failed", [
                    'checkout_id' => $checkout->id,
                    'checkout_token' => $checkout->token,
                    'email' => $checkout->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    protected function sendRecoveryEmail(Checkout $checkout)
    {
        $data = [
            'checkout' => $checkout,
            'checkout_url' => $checkout->getCheckoutUrl(),
            'customer_name' => $checkout->getFullName(),
            'items' => $checkout->line_items,
            'total' => $checkout->total,
        ];

        // Replace with your actual email template and mailing logic
        // Mail::send('emails.abandoned-checkout-recovery', $data, function ($message) use ($checkout) {
        //     $message->to($checkout->email, $checkout->getFullName())
        //             ->subject('Complete Your Purchase - Items Still in Cart');
        // });

        // For now, just log the email would be sent
        Log::info("Recovery email would be sent", [
            'checkout_token' => $checkout->token,
            'email' => $checkout->email,
            'checkout_url' => $checkout->getCheckoutUrl(),
        ]);
    }
}
