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
        Schema::table('plans', function (Blueprint $table) {
            if (! Schema::hasColumn('plans', 'is_contract')) {
                $table->boolean('is_contract')->default(false)->after('interval_count')
                    ->comment('Whether this is a contract plan requiring multiple billing cycles');
            }

            if (! Schema::hasColumn('plans', 'contract_cycles')) {
                $table->unsignedInteger('contract_cycles')->nullable()->after('is_contract')
                    ->comment('Total number of billing cycles required for contract (null = unlimited)');
            }

            // Drop old billing_interval columns if they exist
            if (Schema::hasColumn('plans', 'billing_interval')) {
                $table->dropColumn('billing_interval');
            }
            if (Schema::hasColumn('plans', 'billing_interval_count')) {
                $table->dropColumn('billing_interval_count');
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
