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
        Schema::create('fileables', function (Blueprint $table) {
            $table->string('fileable_type');
            $table->unsignedBigInteger('fileable_id');
            $table->unsignedBigInteger('file_id');
            $table->string('order')->default(0)->nullable();
            $table->string('type')->nullable()->index();

            $table->foreign('file_id')->references('id')->on('files')->cascadeOnDelete();

            $table->index(['fileable_type', 'fileable_id']);
        });
    }
};
