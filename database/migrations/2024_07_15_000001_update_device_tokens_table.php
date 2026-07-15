<?php

use Coderstm\Traits\Helpers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use Helpers;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            $table->string('tokenable_type')->nullable()->after('id');
            $table->string('app_id')->nullable()->after('token');
            $table->dropForeign(['user_id']);
        });

        DB::table('device_tokens')->whereNull('tokenable_type')->update([
            'tokenable_type' => 'App\Models\User',
        ]);

        Schema::table('device_tokens', function (Blueprint $table) {
            $table->renameColumn('user_id', 'tokenable_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            $table->renameColumn('tokenable_id', 'user_id');
        });

        Schema::table('device_tokens', function (Blueprint $table) {
            $table->dropColumn(['tokenable_type', 'app_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
