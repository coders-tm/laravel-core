<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->boolean('is_downgrade')->nullable()->default(false)->after('quantity');
            $table->string('schedule')->nullable()->after('is_downgrade');
            if (!Schema::hasColumn('subscriptions', 'cancels_at')) {
                $table->dateTime('cancels_at')->nullable()->after('ends_at');
            }
        });
    }
};
