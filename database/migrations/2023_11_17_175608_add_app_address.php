<?php

use Coderstm\Models\AppSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        AppSetting::create('address', [
            'company' => 'NitroFIT28',
            'line1' => 'Address Line 1',
            'line2' => 'Address Line 2',
            'city' => 'City',
            'state' => 'State',
            'country' => 'Country',
            'postal_code' => '743273',
        ]);
    }
};
