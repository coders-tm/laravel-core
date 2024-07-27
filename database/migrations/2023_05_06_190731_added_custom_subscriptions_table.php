<?php

use Coderstm\Models\Coupon;
use Coderstm\Traits\Helpers;
use Coderstm\Models\Subscription\Plan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    use Helpers;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignIdFor(Plan::class, 'plan_id')->nullable();
            $table->foreignIdFor(Coupon::class, 'coupon_id')->nullable();
            $table->string('status');
            $table->{$this->jsonable()}('options')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->string('stripe_id')->nullable()->change();

            $table->foreign('plan_id')->references('id')->on('plans')->cascadeOnUpdate()->nullOnDelete();
            $table->foreign('coupon_id')->references('id')->on('coupons')->cascadeOnUpdate()->nullOnDelete();
        });

        Schema::create('subscription_usages', function (Blueprint $table) {
            $table->id();

            $table->string('slug');
            $table->integer('used')->unsigned()->default(0);
            $table->unsignedBigInteger('subscription_id');
            $table->dateTime('reset_at')->nullable();

            $table->unique(['slug', 'subscription_id']);
            $table->foreign('subscription_id')->references('id')->on('subscriptions')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('slug')->references('slug')->on('features')->cascadeOnUpdate()->cascadeOnDelete();
        });

        $this->setAutoIncrement('subscription_usages');
    }
};
