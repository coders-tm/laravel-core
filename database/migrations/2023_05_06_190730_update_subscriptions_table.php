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
            $table->boolean('is_upgrade')->nullable()->default(false)->after('is_downgrade');
            $table->string('next_plan')->nullable()->after('stripe_price');
            $table->string('previous_plan')->nullable()->after('next_plan');
            $table->string('schedule')->nullable()->after('is_downgrade');
            $table->dateTime('cancels_at')->nullable()->after('ends_at');
            $table->dateTime('expires_at')->nullable()->after('ends_at');
        });
    }
};
