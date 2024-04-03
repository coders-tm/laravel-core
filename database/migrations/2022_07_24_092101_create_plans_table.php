<?php

use Coderstm\Traits\Helpers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('label')->nullable();
            $table->longText('description')->nullable();
            $table->longText('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_custom')->default(false);
            $table->string('interval')->default('month');
            $table->string('default_interval')->default('month');
            $table->unsignedInteger('interval_count')->default(1);
            $table->double('custom_fee', 12, 2)->default(0.00);
            $table->double('monthly_fee', 12, 2)->default(0.00);
            $table->double('yearly_fee', 12, 2)->default(0.00);
            $table->unsignedInteger('trial_days')->nullable()->default(0);
            $table->string('stripe_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $this->setAutoIncrement('plans');

        Schema::create('plan_prices', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('plan_id');
            $table->string('stripe_id')->nullable();
            $table->string('interval')->default('month');
            $table->unsignedInteger('interval_count')->default(1);
            $table->double('amount', 12, 2)->default(0.00);
            $table->timestamps();

            $table->foreign('plan_id')->references('id')->on('plans')->cascadeOnUpdate()->cascadeOnDelete();
        });

        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('plan_id');
            $table->string('slug');
            $table->integer('value')->unsigned()->default(0);
            $table->timestamps();

            $table->foreign('plan_id')->references('id')->on('plans')->cascadeOnUpdate()->cascadeOnDelete();
        });

        $this->setAutoIncrement('plan_features');

        Schema::create('plan_usages', function (Blueprint $table) {
            $table->id();

            $table->string('slug');
            $table->integer('used')->unsigned()->default(0);
            $table->unsignedBigInteger('subscription_id');
            $table->dateTime('reset_at')->nullable();

            $table->unique(['slug', 'subscription_id']);
            $table->foreign('subscription_id')->references('id')->on('subscriptions')->cascadeOnUpdate()->cascadeOnDelete();
        });

        $this->setAutoIncrement('plan_usages');
    }
};
