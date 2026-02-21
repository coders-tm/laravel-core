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
        Schema::create('weights', function (Blueprint $table) {
            $table->id();

            $table->string('weightable_type')->nullable();
            $table->unsignedBigInteger('weightable_id')->nullable();

            $table->string('unit')->default('kg');
            $table->decimal('value', 10, 3)->default(0.00);

            $table->index(['weightable_type', 'weightable_id']);
        });

        // set auto increment to 10000
        $this->setAutoIncrement('weights');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('weights');
    }
};
