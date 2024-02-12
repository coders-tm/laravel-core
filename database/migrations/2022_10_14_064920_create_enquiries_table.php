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
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();

            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('subject')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->nullable();
            $table->boolean('seen')->nullable()->default(false);
            $table->boolean('source')->nullable()->default(true);
            $table->boolean('is_archived')->nullable()->default(false);
            $table->boolean('user_archived')->nullable()->default(false);
            $table->unsignedBigInteger('admin_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('admin_id')->references('id')->on('admins')->nullOnDelete();
        });

        Schema::create('replies', function (Blueprint $table) {
            $table->id();
            $table->string('user_type')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('enquiry_id');
            $table->text('message')->nullable();
            $table->boolean('seen')->nullable()->default(false);
            $table->boolean('staff_only')->nullable()->default(false);
            $table->timestamps();

            $table->foreign('enquiry_id')->references('id')->on('enquiries')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }
};
