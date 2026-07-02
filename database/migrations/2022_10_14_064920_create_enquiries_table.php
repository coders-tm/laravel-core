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
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();

            $table->string('name')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable();
            $table->string('subject')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->nullable()->index();
            $table->boolean('seen')->default(false)->index();
            $table->boolean('source')->default(true)->index();
            $table->boolean('is_archived')->default(false)->index();
            $table->boolean('user_archived')->default(false)->index();
            $table->unsignedBigInteger('admin_id')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('admin_id')->references('id')->on('admins')->nullOnDelete();
        });

        $this->setAutoIncrement('enquiries');

        Schema::create('replies', function (Blueprint $table) {
            $table->id();

            $table->string('user_type')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();

            $table->unsignedBigInteger('enquiry_id')->index();

            $table->text('message')->nullable();
            $table->boolean('seen')->default(false)->index();
            $table->boolean('staff_only')->default(false)->index();
            $table->timestamps();

            $table->foreign('enquiry_id')->references('id')->on('enquiries')->cascadeOnDelete();

            $table->index(['user_type', 'user_id']);
        });

        $this->setAutoIncrement('replies');
    }
};
