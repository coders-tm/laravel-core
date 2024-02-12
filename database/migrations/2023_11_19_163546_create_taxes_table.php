<?php

use Coderstm\Models\Tax;
use League\ISO3166\ISO3166;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();

            $table->string('country')->nullable();
            $table->string('code')->nullable();
            $table->string('state')->nullable();
            $table->string('label')->nullable();
            $table->boolean('compounded')->nullable()->default(false);
            $table->double('rate', 10, 2)->default(0.00);
            $table->tinyInteger('priority')->default(0);

            $table->timestamps();
        });

        $country = (new ISO3166)->name(config('app.country'));
        if ($country) {
            Tax::updateOrCreate([
                'country' => config('app.country'),
                'label' => 'VAT',
                'code' => $country['alpha2'],
                'state' => '*',
                'rate' => 10,
                'priority' => 0,
            ]);
        }

        Tax::updateOrCreate([
            'country' => 'Rest of world',
            'label' => 'VAT',
            'code' => '*',
            'state' => '*',
            'rate' => 0,
            'priority' => 0,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('taxes');
    }
};
