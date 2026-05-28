<?php

use Coderstm\Models\Coupon;
use Coderstm\Models\Subscription\Plan;
use Coderstm\Traits\Helpers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use Helpers;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('provider')->nullable()->index();
            $table->integer('quantity')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->foreignIdFor(Plan::class, 'plan_id')->nullable()->index();
            $table->string('next_plan')->nullable();
            $table->foreignIdFor(Coupon::class, 'coupon_id')->nullable()->index();
            $table->{$this->jsonable()}('metadata')->nullable();
            $table->string('billing_interval')->nullable()->comment('Billing cycle frequency (day, week, month, year)');
            $table->unsignedInteger('billing_interval_count')->nullable()->comment('Billing interval count (e.g., 2 for bi-weekly)');
            $table->unsignedInteger('total_cycles')->nullable()->comment('Total number of billing cycles for contract');
            $table->unsignedInteger('current_cycle')->default(0)->comment('Current billing cycle number');
            $table->string('status')->nullable()->index();
            $table->boolean('is_downgrade')->default(false);
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('starts_at')->nullable()->index();
            $table->dateTime('expires_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('canceled_at')->nullable()->index();
            $table->timestamp('frozen_at')->nullable()->comment('When the subscription was frozen (paused)');
            $table->timestamp('release_at')->nullable()->comment('When the subscription should automatically unfreeze');
            $table->timestamps();

            $table->foreign('plan_id')->references('id')->on('plans')->nullOnDelete();
            $table->foreign('coupon_id')->references('id')->on('coupons')->nullOnDelete();

            $table->unsignedBigInteger('user_id')->nullable()->change()->index();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
