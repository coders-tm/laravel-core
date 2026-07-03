<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('subscriptions', 'credit_resets_at')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->timestamp('credit_resets_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('credit_resets_at');
        });
    }
};
