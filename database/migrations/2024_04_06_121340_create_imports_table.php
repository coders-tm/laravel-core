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
     */
    public function up(): void
    {
        Schema::dropIfExists('imports');
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->string('model')->nullable();
            $table->unsignedBigInteger('file_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status')->nullable();
            $table->{$this->jsonable()}('options')->nullable();
            $table->{$this->jsonable()}('success')->nullable();
            $table->{$this->jsonable()}('failed')->nullable();
            $table->{$this->jsonable()}('skipped')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imports');
    }
};
