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
        Schema::table('discount_lines', function (Blueprint $table) {
            // Add coupon tracking fields
            if (! Schema::hasColumn('discount_lines', 'coupon_id')) {
                $table->unsignedBigInteger('coupon_id')->nullable()->after('description');

                // Add foreign key constraint
                $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('set null');
            }
            if (! Schema::hasColumn('discount_lines', 'coupon_code')) {
                $table->string('coupon_code')->nullable()->after('coupon_id');
            }

            // Add index for performance
            if (! Schema::hasIndex('discount_lines', ['coupon_id', 'coupon_code'])) {
                $table->index(['coupon_id', 'coupon_code']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discount_lines', function (Blueprint $table) {
            $table->dropForeign(['coupon_id']);
            $table->dropIndex(['coupon_id', 'coupon_code']);
            $table->dropColumn(['coupon_id', 'coupon_code']);
        });
    }
};
