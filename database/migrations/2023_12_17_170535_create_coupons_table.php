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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('stripe_id');
            $table->string('promotion_code')->unique();
            $table->string('promotion_id');
            $table->{$this->jsonable()}('applies_to')->nullable();
            $table->string('currency')->nullable();
            $table->string('duration');
            $table->unsignedInteger('duration_in_months')->nullable();
            $table->unsignedInteger('max_redemptions')->nullable();
            $table->unsignedBigInteger('amount_off')->nullable();
            $table->unsignedSmallInteger('percent_off')->nullable();
            $table->boolean('fixed')->default(false);
            $table->boolean('active')->default(true);
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $this->setAutoIncrement('coupons');

        Schema::create('coupon_plans', function (Blueprint $table) {
            $table->unsignedBigInteger('coupon_id');
            $table->unsignedBigInteger('plan_id');

            $table->foreign('plan_id')->references('id')->on('plans')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('coupon_id')->references('id')->on('coupons')->cascadeOnUpdate()->cascadeOnDelete();
        });

        Schema::create('redeems', function (Blueprint $table) {
            $table->id();
            $table->string('redeemable_type');
            $table->unsignedBigInteger('redeemable_id');
            $table->unsignedBigInteger('coupon_id');
            $table->double('amount', 20, 2)->default(0.00)->nullable();
            $table->timestamps();
        });

        $this->setAutoIncrement('redeems');
    }
};
