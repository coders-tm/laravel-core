<?php

namespace Coderstm\Tests;

use Coderstm\Coderstm;
use Coderstm\Models\Admin;
use Illuminate\Support\Str;
use Coderstm\Models\Enquiry;
use InvalidArgumentException;
use App\Models\User;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class BaseTestCase extends OrchestraTestCase
{
    use WithWorkbench;

    protected function getEnvironmentSetUp($app)
    {
        $apiKey = config('cashier.secret');

        if ($apiKey && !Str::startsWith($apiKey, 'sk_test_')) {
            throw new InvalidArgumentException('Tests may not be run with a production Stripe key.');
        }

        Coderstm::useUserModel(User::class);
        Coderstm::useAdminModel(Admin::class);
        Coderstm::useEnquiryModel(Enquiry::class);
    }
}
