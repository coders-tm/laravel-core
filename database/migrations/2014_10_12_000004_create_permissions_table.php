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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('module_id')->nullable();
            $table->string('action')->nullable();
            $table->string('scope')->nullable()->unique();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->foreign('module_id')->references('id')->on('modules')->cascadeOnUpdate()->cascadeOnDelete();
        });

        $this->setAutoIncrement('permissions');
    }
};
