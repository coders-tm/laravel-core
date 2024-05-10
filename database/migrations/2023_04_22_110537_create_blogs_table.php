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
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('description');
            $table->string('meta_title')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->string('meta_description')->nullable();
            $table->boolean('is_active')->nullable()->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        $this->setAutoIncrement('blogs');

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->string('userable_type');
            $table->unsignedBigInteger('userable_id');
            $table->string('commentable_type');
            $table->unsignedBigInteger('commentable_id');
            $table->longText('message');
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
