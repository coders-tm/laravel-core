<?php

namespace Coderstm\Tests\Feature;

use Carbon\Carbon;
use App\Models\User;
use Coderstm\Tests\Feature\FeatureTestCase;

class CustomerTest extends FeatureTestCase
{
    public function test_customer_can_be_put_on_a_generic_trial()
    {
        $user = new User;

        $this->assertFalse($user->onGenericTrial());

        $user->trial_ends_at = Carbon::tomorrow();

        $this->assertTrue($user->onTrial());
        $this->assertTrue($user->onGenericTrial());

        $user->trial_ends_at = Carbon::today()->subDays(5);

        $this->assertFalse($user->onGenericTrial());
    }

    public function test_we_can_check_if_a_generic_trial_has_expired()
    {
        $user = new User;

        $user->trial_ends_at = Carbon::yesterday();

        $this->assertTrue($user->hasExpiredTrial());
        $this->assertTrue($user->hasExpiredGenericTrial());
    }
}
