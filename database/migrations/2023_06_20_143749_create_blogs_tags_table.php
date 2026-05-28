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
        Schema::create('blogs_tags', function (Blueprint $table) {
            $table->id();

            $table->string('label')->nullable()->index();
            $table->string('slug')->nullable()->unique()->index();

            $table->timestamps();
            $table->softDeletes();
        });

        $this->setAutoIncrement('blogs_tags');

        Schema::create('blogs_taggables', function (Blueprint $table) {
            $table->string('taggable_type')->nullable();
            $table->unsignedBigInteger('taggable_id')->nullable();

            $table->unsignedBigInteger('tag_id')->index();

            $table->foreign('tag_id')->references('id')->on('blogs_tags')->cascadeOnDelete();

            $table->index(['taggable_type', 'taggable_id']);
        });
    }
};
