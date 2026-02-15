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
        // Notification templates table (avoids collision with Laravel's database notifications table)
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();

            $table->string('label')->nullable();
            $table->string('subject')->nullable();
            $table->string('type')->nullable()->index();
            $table->text('content')->nullable();
            $table->boolean('is_default')->default(false)->index();

            $table->timestamps();
            $table->softDeletes();
        });

        $this->setAutoIncrement('notification_templates');
    }
};
