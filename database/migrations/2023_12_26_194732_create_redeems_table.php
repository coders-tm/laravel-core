<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('redeems', function (Blueprint $table) {
            $table->id();
            $table->string('redeemable_type');
            $table->unsignedBigInteger('redeemable_id');
            $table->unsignedBigInteger('coupon_id');
            $table->double('amount', 20, 2)->default(0.00)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('redeems');
    }
};
