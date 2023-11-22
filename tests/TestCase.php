<?php

namespace Coderstm\Tests;

use Coderstm\Coderstm;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Coderstm\Tests\Fixtures\User;
use Coderstm\Providers\CoderstmServiceProvider;
use Coderstm\Providers\CoderstmEventServiceProvider;
use Laravel\Cashier\CashierServiceProvider;
use Coderstm\Providers\CoderstmPermissionsServiceProvider;
use Coderstm\Models\Admin;
use Coderstm\Models\Enquiry;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
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

    protected function getPackageProviders($app)
    {
        return [
            CashierServiceProvider::class,
            CoderstmServiceProvider::class,
            CoderstmPermissionsServiceProvider::class,
            CoderstmEventServiceProvider::class,
        ];
    }
}
