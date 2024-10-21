<?php

use Coderstm\Traits\Helpers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use Helpers;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->boolean('is_downgrade')->default(false)->after('quantity');
            $table->boolean('is_upgrade')->default(false)->after('is_downgrade');
            $table->string('next_plan')->nullable()->after('stripe_price')->index();
            $table->string('previous_plan')->nullable()->after('next_plan')->index();
            $table->string('schedule')->nullable()->after('is_downgrade');
            $table->dateTime('cancels_at')->nullable()->after('ends_at')->index();
            $table->dateTime('expires_at')->nullable()->after('ends_at')->index();

            $table->unsignedBigInteger('user_id')->nullable()->change()->index();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        $this->setAutoIncrement('subscriptions');
    }
};
