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
     */
    public function up(): void
    {
        Schema::create('stripe_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('stripe_id')->unique();
            $table->string('name')->nullable();
            $table->{$this->jsonable()}('card')->nullable();
            $table->string('brand')->nullable();
            $table->string('card_number')->nullable();
            $table->string('last_four_digit')->nullable();
            $table->string('exp_date')->nullable();
            $table->boolean('is_default')->nullable()->default(0);
            $table->timestamps();
        });

        $this->setAutoIncrement('stripe_payment_methods');
    }
};
