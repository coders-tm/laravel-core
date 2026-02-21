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
     */
    public function up(): void
    {
        // Skip if coupons already exists
        if (Schema::hasTable('coupons')) {
            return;
        }

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('type')->default('plan')->comment('Type of coupon: plan, product, or cart');
            $table->enum('discount_type', ['percentage', 'fixed', 'override'])->default('percentage');
            $table->double('value', 10, 2)->default(0);
            $table->string('promotion_code')->unique()->index();
            $table->string('duration');
            $table->unsignedInteger('duration_in_months')->nullable();
            $table->unsignedInteger('max_redemptions')->nullable();
            $table->boolean('auto_apply')->default(false);
            $table->boolean('active')->default(true)->index();
            $table->dateTime('expires_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        $this->setAutoIncrement('coupons');

        Schema::create('coupon_plans', function (Blueprint $table) {
            $table->unsignedBigInteger('coupon_id');
            $table->unsignedBigInteger('plan_id');

            $table->foreign('plan_id')->references('id')->on('plans')->cascadeOnDelete();
            $table->foreign('coupon_id')->references('id')->on('coupons')->cascadeOnDelete();

            $table->index(['coupon_id', 'plan_id']);
        });

        Schema::create('redeems', function (Blueprint $table) {
            $table->id();
            $table->string('redeemable_type');
            $table->unsignedBigInteger('redeemable_id');
            $table->unsignedBigInteger('coupon_id')->index();
            $table->double('amount', 20, 2)->default(0.00)->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->timestamps();

            $table->foreign('coupon_id')->references('id')->on('coupons')->cascadeOnDelete();

            $table->index(['redeemable_type', 'redeemable_id']);
        });

        $this->setAutoIncrement('redeems');
    }
};
