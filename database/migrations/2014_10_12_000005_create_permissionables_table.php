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
        Schema::create('permissionables', function (Blueprint $table) {
            $table->string('permissionable_type');
            $table->unsignedBigInteger('permissionable_id');
            $table->string('scope');
            $table->boolean('access')->default(true);

            $table->unique(['permissionable_type', 'permissionable_id', 'scope'], 'permissionables_unique');
        });
    }
};
