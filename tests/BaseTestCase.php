<?php

namespace Tests;

use Coderstm\Contracts\ConfigurationInterface;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class BaseTestCase extends OrchestraTestCase
{
    use WithWorkbench;

    protected function getEnvironmentSetUp($app)
    {
        $apiKey = config('stripe.secret');

        if ($apiKey && ! Str::startsWith($apiKey, 'sk_test_')) {
            throw new InvalidArgumentException('Tests may not be run with a production Stripe key.');
        }

        // Configure admin email for notification tests
        $app['config']->set('coderstm.admin_email', 'admin@example.com');

        // Ensure default currency is set for payment tests
        $app['config']->set('app.currency', 'USD');

        // Stub the license verifier to avoid real HTTP calls during boot
        $app->singleton(ConfigurationInterface::class, function () {
            return new class implements ConfigurationInterface
            {
                public function isValid()
                {
                    return true;
                }

                public function optimizeResponse($request, $response)
                {
                    return $response;
                }
            };
        });
    }
}
