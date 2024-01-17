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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
