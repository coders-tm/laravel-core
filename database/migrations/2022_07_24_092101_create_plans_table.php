<?php

use Coderstm\Traits\Helpers;
use Coderstm\Models\Subscription\Plan;
use Illuminate\Support\Facades\Schema;
use Coderstm\Models\Subscription\Feature;
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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('label')->nullable();
            $table->string('slug')->unique()->index();
            $table->longText('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->string('default_interval')->default('month');
            $table->string('interval')->default('month')->index();
            $table->unsignedInteger('interval_count')->default(1);
            $table->double('price', 12, 2)->default(0.00);
            $table->unsignedInteger('trial_days')->nullable()->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        $this->setAutoIncrement('plans');

        Schema::create('plan_features', function (Blueprint $table) {
            $table->foreignIdFor(Plan::class);
            $table->foreignIdFor(Feature::class);
            $table->integer('value')->default(0);

            $table->index(['plan_id', 'feature_id']);
        });
    }
};
