<?php

use Coderstm\Models\Subscription;
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
        Schema::create('subscription_features', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Subscription::class)->constrained()->cascadeOnDelete();
            $table->string('slug')->index();
            $table->string('label');
            $table->enum('type', ['integer', 'boolean'])->default('integer');
            $table->boolean('resetable')->default(false);
            $table->integer('value')->default(0);
            $table->unsignedInteger('used')->default(0);
            $table->dateTime('reset_at')->nullable();
            $table->timestamps();

            $table->unique(['subscription_id', 'slug']);
            $table->index(['subscription_id', 'slug']);
        });

        $this->setAutoIncrement('subscription_features');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_features');
    }
};
