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
        // Skip if features table already exists
        if (Schema::hasTable('features')) {
            return;
        }

        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('slug')->unique()->index();
            $table->enum('type', ['integer', 'boolean'])->nullable()->default('integer');
            $table->boolean('resetable')->nullable()->default(false);
            $table->mediumText('description')->nullable();
            $table->timestamps();
        });

        $this->setAutoIncrement('features');
    }
};
