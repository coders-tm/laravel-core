<?php

namespace Database\Seeders;

use Coderstm\Traits\Helpers;
use Illuminate\Database\Seeder;
use Coderstm\Models\PaymentMethod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PaymentMethodSeeder extends Seeder
{
    use Helpers, WithoutModelEvents;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $paymentMethods = json_decode(file_get_contents(database_path('data/payment-methods.json')), true);

        foreach ($paymentMethods as $paymentMethod) {
            $webhook = isset($paymentMethod['webhook']) ? str_replace('{API_URL}', base_url('api'), $paymentMethod['webhook']) : null;
            $credentials = isset($paymentMethod['credentials']) ? $paymentMethod['credentials'] : [];

            if ($paymentMethod['provider'] === PaymentMethod::STRIPE) {
                $credentials = collect($credentials)->map(function ($item) {
                    switch ($item['key']) {
                        case 'API_KEY':
                            $item['value'] = config('cashier.key');
                            break;
                        case 'API_SECRET':
                            $item['value'] = config('cashier.secret');
                            break;
                        case 'WEBHOOK_SECRET':
                            $item['value'] = config('cashier.webhook.secret');
                            break;
                    }

                    return $item;
                })->toArray();
            }

            PaymentMethod::firstOrCreate([
                'provider' => $paymentMethod['provider']
            ], array_merge($paymentMethod, [
                'webhook' => $webhook,
                'credentials' => $credentials,
            ]));
        }
    }
}
