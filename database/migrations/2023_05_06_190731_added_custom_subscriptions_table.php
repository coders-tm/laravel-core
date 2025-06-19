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
            $table->string('provider')->nullable()->index()->after('id');
            $table->foreignIdFor(Plan::class, 'plan_id')->nullable()->index();
            $table->foreignIdFor(Coupon::class, 'coupon_id')->nullable()->index();
            $table->string('status')->nullable()->index();
            $table->string('stripe_status')->nullable()->change();
            $table->{$this->jsonable()}('options')->nullable();
            $table->timestamp('starts_at')->nullable()->index()->after('trial_ends_at');
            $table->timestamp('canceled_at')->nullable()->index()->after('ends_at');
            $table->string('stripe_id')->nullable()->change();

            $table->foreign('plan_id')->references('id')->on('plans')->nullOnDelete();
            $table->foreign('coupon_id')->references('id')->on('coupons')->nullOnDelete();
        });

        Schema::create('subscription_usages', function (Blueprint $table) {
            $table->id();

            $table->string('slug');
            $table->unsignedInteger('used')->default(0);
            $table->unsignedBigInteger('subscription_id');
            $table->dateTime('reset_at')->nullable();

            $table->unique(['slug', 'subscription_id']);
            $table->index(['slug', 'subscription_id']);

            $table->foreign('subscription_id')->references('id')->on('subscriptions')->cascadeOnDelete();
            $table->foreign('slug')->references('slug')->on('features')->cascadeOnDelete();
        });

        $this->setAutoIncrement('subscription_usages');
    }
};
