<?php

use Coderstm\Models\Log;
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
        Schema::create('logs', function (Blueprint $table) {
            $table->id();

            $table->string('logable_type')->nullable();
            $table->unsignedBigInteger('logable_id')->nullable();

            $table->string('type')->default('default');
            $table->string('status')->default(Log::STATUS_SUCCESS)->nullable();
            $table->text('message')->nullable();
            $table->{$this->jsonable()}('options')->nullable();
            $table->string('admin_id')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        $this->setAutoIncrement('logs');
    }
};
