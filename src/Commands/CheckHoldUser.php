<?php

namespace Coderstm\Commands;

use Coderstm\Models\User;
use Coderstm\Enum\AppStatus;
use Illuminate\Console\Command;
use Coderstm\Notifications\Admins\HoldMemberNotification;

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
        $users = User::where('release_at', '<=', now());

        foreach ($users->cursor() as $user) {
            $user->update([
                'status' => AppStatus::ACTIVE->value,
                'release_at' => null
            ]);

            try {
                $subscription = $user->subscription();
                if ($subscription->canceled()) {
                    $subscription = $user->newSubscription('default', $subscription->stripe_price)->create();
                }
            } catch (\Exception $e) {
                report($e);
            }

            admin_notify(new HoldMemberNotification($user));
            $this->info("User #{$user->id} has been released!");
        }
    }
}
