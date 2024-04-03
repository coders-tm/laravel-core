<?php

namespace Database\Seeders;

use Coderstm\Traits\Helpers;
use Illuminate\Database\Seeder;
use Coderstm\Models\PaymentMethod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PaymentMethodSeeder extends Seeder
{
    use Helpers;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $paymentMethods = json_decode(file_get_contents(database_path('data/payment-methods.json')), true);

        foreach ($paymentMethods as $paymentMethod) {
            $webhook = isset($paymentMethod['webhook']) ? str_replace('{API_URL}', app_domain('api'), $paymentMethod['webhook']) : null;

            PaymentMethod::firstOrCreate([
                'provider' => $paymentMethod['provider']
            ], array_merge($paymentMethod, [
                'webhook' => $webhook
            ]));
        }
    }
}
