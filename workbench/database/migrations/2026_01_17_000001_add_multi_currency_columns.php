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
        if (! Schema::hasColumn('users', 'currency')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('currency')->nullable()->after('is_active')->default('USD');
            });
        }

        if (! Schema::hasColumn('payment_methods', 'supported_currencies')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->json('supported_currencies')->nullable()->after('methods');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // do nothing
    }
};
