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
        Schema::table('subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('subscriptions', 'billing_interval')) {
                $table->string('billing_interval')->nullable()->after('status')
                    ->comment('Billing cycle frequency (day, week, month, year)');
            }
            if (! Schema::hasColumn('subscriptions', 'billing_interval_count')) {
                $table->unsignedInteger('billing_interval_count')->nullable()->after('billing_interval')
                    ->comment('Billing interval count (e.g., 2 for bi-weekly)');
            }
            if (! Schema::hasColumn('subscriptions', 'total_cycles')) {
                $table->unsignedInteger('total_cycles')->nullable()->after('billing_interval_count')
                    ->comment('Total number of billing cycles for contract');
            }
            if (! Schema::hasColumn('subscriptions', 'current_cycle')) {
                $table->unsignedInteger('current_cycle')->default(0)->after('total_cycles')
                    ->comment('Current billing cycle number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // don't drop columns to prevent data loss
    }
};
