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
        if (! Schema::hasColumn('orders', 'paid_total')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->double('paid_total', 20, 2)->default(0.00)->after('grand_total');
                $table->double('refund_total', 20, 2)->default(0.00)->after('paid_total');
                $table->integer('line_items_quantity')->default(0)->after('refund_total');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
