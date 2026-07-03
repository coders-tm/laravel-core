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
            if (! Schema::hasColumn('plans', 'grace_period_days')) {
                $table->unsignedInteger('grace_period_days')
                    ->default(0)
                    ->after('freeze_fee')
                    ->comment('Number of grace period days before subscription expires after non-payment (0 = expires immediately)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (Schema::hasColumn('plans', 'grace_period_days')) {
                $table->dropColumn('grace_period_days');
            }
        });
    }
};
