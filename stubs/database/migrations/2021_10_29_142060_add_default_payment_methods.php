<?php

use Coderstm\Models\Notification;
use Coderstm\Models\PaymentMethod;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $paymentMethods = json_decode(file_get_contents(database_path('data/payment-methods.json')), true);

        foreach ($paymentMethods as $paymentMethod) {
            $webhook = isset($paymentMethod['webhook']) ? str_replace('{API_URL}', app_domain('api'), $paymentMethod['webhook']) : null;

            if ($paymentMethod['provider'] == PaymentMethod::STRIPE) {
                $paymentMethod['credentials'] = collect($paymentMethod['credentials'])->map(function ($item) {
                    if ($item['key'] == 'API_KEY') {
                        $item['value'] = config('cashier.key');
                    } else if ($item['key'] == 'API_SECRET') {
                        $item['value'] = config('cashier.secret');
                    } else if ($item['key'] == 'WEBHOOK_SECRET') {
                        $item['value'] = config('cashier.webhook.secret');
                    }
                    return $item;
                })->all();
            }

            PaymentMethod::updateOrCreate([
                'provider' => $paymentMethod['provider']
            ], array_merge($paymentMethod, [
                'webhook' => $webhook
            ]));
        }
    }
};
