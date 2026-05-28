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
        Schema::create('collection_conditions', function (Blueprint $table) {
            $table->id();

            $table->string('type')->nullable();
            $table->string('relation')->nullable();
            $table->string('value')->nullable();

            $table->unsignedBigInteger('collection_id')->index();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('collection_id')->references('id')->on('collections')->cascadeOnDelete();
        });

        // set auto increment to 10000
        $this->setAutoIncrement('collection_conditions');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('collection_conditions');
    }
};
