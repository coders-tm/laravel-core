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
        if (! Schema::hasColumn('refunds', 'to_wallet')) {
            Schema::table('refunds', function (Blueprint $table) {
                $table->boolean('to_wallet')->default(false)->after('payment_id');
                $table->foreignId('wallet_transaction_id')->nullable()->after('to_wallet')->constrained()->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->dropForeign(['wallet_transaction_id']);
            $table->dropColumn(['to_wallet', 'wallet_transaction_id']);
        });
    }
};
