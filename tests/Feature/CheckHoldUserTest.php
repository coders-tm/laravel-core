<?php

namespace Coderstm\Tests\Feature;

use Coderstm\Models\User;
use Coderstm\Enum\AppStatus;
use Coderstm\Models\Subscription;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\AnonymousNotifiable;
use Coderstm\Notifications\Admins\HoldMemberNotification;

class CheckHoldUserTest extends FeatureTestCase
{
    public function test_it_releases_users_and_renews_subscription()
    {
        // Arrange: Create a user with a release_at date in the past and a canceled subscription
        $user = User::factory()->create([
            'status' => AppStatus::ACTIVE->value,
            'release_at' => now()->subDay(),
        ]);

        Subscription::withoutEvents(function () use ($user) {
            return Subscription::factory()->create([
                'user_id' => $user->id,
                'status' => Subscription::STATUS_CANCELED,
            ]);
        });

        // Mock Notification
        Notification::fake();

        // Act: Run the command
        $this->artisan('coderstm:users-hold')
            ->expectsOutput("User #{$user->id} has been released!")
            ->assertExitCode(0);

        // Assert: The user's status is updated, release_at is null, and a new subscription is created
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'status' => AppStatus::ACTIVE->value,
            'release_at' => null,
        ]);

        // Assert that the notification was sent to the correct admin email
        Notification::assertSentTo(
            new AnonymousNotifiable,
            HoldMemberNotification::class,
            function ($notification, $channels, $notifiable) {
                return $notifiable->routes['mail'] === [config('coderstm.admin_email') => config('app.name')];
            }
        );
    }
}
