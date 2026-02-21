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
        Schema::create('statuses', function (Blueprint $table) {
            $table->id();

            $table->string('statusable_type')->nullable();
            $table->unsignedBigInteger('statusable_id')->nullable();

            $table->string('label');

            $table->index(['statusable_type', 'statusable_id']);
        });

        $this->setAutoIncrement('statuses');
    }
};
