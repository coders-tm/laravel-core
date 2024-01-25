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
            $table->boolean('is_upgrade')->nullable()->default(false)->after('is_downgrade');
            $table->string('previous_plan')->nullable()->after('is_upgrade');
        });
    }
};
