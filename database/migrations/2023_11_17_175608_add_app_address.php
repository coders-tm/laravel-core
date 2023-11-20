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
            'line2' => '',
            'city' => 'North 24 Pargans',
            'state' => 'West Bengal',
            'state_code' => 'WB',
            'country' => 'India',
            'country_code' => 'IN',
            'postal_code' => '743273',
        ]);
    }
};
