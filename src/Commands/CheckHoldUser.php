<?php

namespace Coderstm\Commands;

use Coderstm\Models\User;
use Coderstm\Enum\AppStatus;
use Illuminate\Console\Command;
use Coderstm\Notifications\HoldMemberNotification;

class CheckHoldUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coderstm:users-hold';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check hold users';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        User::where('release_at', '<=', now())->each(function ($user) {
            $user->update([
                'status' => AppStatus::ACTIVE->value,
                'release_at' => null
            ]);

            try {
                $subscription = $user->subscription();
                if ($subscription->canceled()) {
                    $subscription = $user->newSubscription('default', $subscription->stripe_price)->create();
                }
            } catch (\Throwable $th) {
                // report($th);
            }

            admin_notify(new HoldMemberNotification($user));
            $this->info("User #{$user->id} has been released!");
        });
    }
}
