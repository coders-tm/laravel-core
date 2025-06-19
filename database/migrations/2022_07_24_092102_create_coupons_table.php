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
            $table->string('promotion_code')->unique()->index();
            $table->{$this->jsonable()}('applies_to')->nullable();
            $table->string('duration');
            $table->unsignedInteger('duration_in_months')->nullable();
            $table->unsignedInteger('max_redemptions')->nullable();
            $table->unsignedBigInteger('amount_off')->nullable();
            $table->unsignedSmallInteger('percent_off')->nullable();
            $table->boolean('fixed')->default(false);
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
