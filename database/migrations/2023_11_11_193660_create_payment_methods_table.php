<?php

use Coderstm\Traits\Helpers;
use Coderstm\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    use Helpers;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('payment_methods')) {
            Schema::create('payment_methods', function (Blueprint $table) {
                $table->id();

                $table->string('name');
                $table->string('provider')->default('manual');
                $table->string('link')->nullable();
                $table->string('logo')->nullable();
                $table->text('description')->nullable();
                $table->{$this->jsonable()}('credentials')->nullable();
                $table->{$this->jsonable()}('methods')->nullable();
                $table->boolean('active')->default(false);
                $table->enum('capture', ['automatic', 'manual'])->nullable()->default('manual');
                $table->string('additional_details')->nullable();
                $table->string('payment_instructions')->nullable();
                $table->boolean('test_mode')->default(false);
                $table->string('transaction_fee')->default(0);
                $table->string('webhook')->nullable();

                $table->timestamps();
                $table->softDeletes();
            });
            $this->setAutoIncrement('payment_methods');
        }

        foreach (payment_methods() as $paymentMethod) {
            $webhook = isset($paymentMethod['webhook']) ? str_replace('{API_URL}', app_url('api'), $paymentMethod['webhook']) : null;

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

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_methods');
    }
};
