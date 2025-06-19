<?php

namespace Coderstm\Tests\Feature;

use Stripe\StripeClient;
use Coderstm\Tests\TestCase;
use Laravel\Cashier\Cashier;
use App\Models\User;
use Stripe\ApiRequestor as StripeApiRequestor;
use Stripe\HttpClient\CurlClient as StripeCurlClient;

abstract class FeatureTestCase extends TestCase
{
    protected function setUp(): void
    {
        if (!getenv('STRIPE_SECRET')) {
            $this->markTestSkipped('Stripe secret key not set.');
        }

        parent::setUp();

        config(['app.url' => 'http://localhost']);

        $curl = new StripeCurlClient([CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1]);
        $curl->setEnableHttp2(false);
        StripeApiRequestor::setHttpClient($curl);
    }

    protected static function stripe(array $options = []): StripeClient
    {
        return Cashier::stripe(array_merge(['api_key' => getenv('STRIPE_SECRET')], $options));
    }

    protected function createCustomer($description = 'dipak', array $options = []): User
    {
        return User::create(array_merge([
            'email' => "{$description}@coderstm.com",
            'first_name' => 'Dipak',
            'last_name' => 'Sarkar',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        ], $options));
    }
}
