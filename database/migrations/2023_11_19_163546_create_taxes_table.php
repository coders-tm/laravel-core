<?php

use Coderstm\Models\Tax;
use Coderstm\Traits\Helpers;
use League\ISO3166\ISO3166;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();

            $table->string('country')->nullable();
            $table->string('code')->nullable();
            $table->string('state')->nullable();
            $table->string('label')->nullable();
            $table->boolean('compounded')->nullable()->default(false);
            $table->double('rate', 10, 2)->default(0.00);
            $table->tinyInteger('priority')->default(0);

            $table->timestamps();
        });

        $this->setAutoIncrement('taxes');
    }
};
