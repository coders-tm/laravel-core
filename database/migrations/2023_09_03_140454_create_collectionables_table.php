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
        Schema::create('collectionables', function (Blueprint $table) {
            $table->string('collectionable_type');
            $table->unsignedBigInteger('collectionable_id');
            $table->unsignedBigInteger('collection_id');

            $table->foreign('collection_id')->references('id')->on('collections')->cascadeOnDelete();

            $table->primary(['collectionable_type', 'collectionable_id', 'collection_id']);

            $table->index(['collectionable_type', 'collectionable_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('collectionables');
    }
};
