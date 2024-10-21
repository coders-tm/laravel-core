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

            $table->string('type')->default('default')->index();
            $table->string('status')->default(Log::STATUS_SUCCESS)->index();
            $table->text('message')->nullable();
            $table->{$this->jsonable()}('options')->nullable();

            $table->unsignedBigInteger('admin_id')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['logable_type', 'logable_id']);
        });

        $this->setAutoIncrement('logs');
    }
};
