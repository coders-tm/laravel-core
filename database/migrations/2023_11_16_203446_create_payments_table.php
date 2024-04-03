<?php

use Coderstm\Traits\Helpers;
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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->string('paymentable_type')->nullable();
            $table->unsignedBigInteger('paymentable_id')->nullable();

            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->string('transaction_id')->nullable();
            $table->double('amount', 20, 2)->default(0.00);
            $table->boolean('capturable')->nullable()->default(true);
            $table->string('status')->nullable();
            $table->text('note')->nullable();
            $table->{$this->jsonable()}('options')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->cascadeOnUpdate()->cascadeOnDelete();
        });

        $this->setAutoIncrement('payments');
    }
};
