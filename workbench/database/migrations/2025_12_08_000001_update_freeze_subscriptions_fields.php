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
        // Add fields to plans table if it doesn't exist
        if (! Schema::hasColumn('plans', 'allow_freeze')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->unsignedInteger('contract_cycles')->nullable()->after('is_contract')
                    ->comment('Total number of billing cycles required for contract (null = unlimited)');
                $table->boolean('allow_freeze')->default(true)->after('contract_cycles')
                    ->comment('Whether this plan allows membership freeze');
                $table->decimal('freeze_fee', 10, 2)->nullable()->after('allow_freeze')
                    ->comment('Fee charged per billing cycle during freeze (null = use global config)');
            });
        }

        // Add fields to subscriptions table if it doesn't exist
        if (! Schema::hasColumn('subscriptions', 'frozen_at')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->timestamp('frozen_at')->nullable()->after('canceled_at')
                    ->comment('When the subscription was frozen (paused)');
                $table->timestamp('release_at')->nullable()->after('frozen_at')
                    ->comment('When the subscription should automatically unfreeze');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // don't drop columns to prevent data loss
    }
};
