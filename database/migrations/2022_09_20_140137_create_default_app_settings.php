<?php

use Coderstm\Models\AppSetting;
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
        AppSetting::create('opening-times', [
            '0' => ['name' => 'Monday', 'open_at' => '06:00', 'close_at' => '21:00', 'is_closed' => false],
            '1' => ['name' => 'Tuesday', 'open_at' => '06:00', 'close_at' => '21:00', 'is_closed' => false],
            '2' => ['name' => 'Wednesday', 'open_at' => '05:30', 'close_at' => '21:00', 'is_closed' => false],
            '3' => ['name' => 'Thursday', 'open_at' => '06:00', 'close_at' => '21:00', 'is_closed' => false],
            '4' => ['name' => 'Friday', 'open_at' => '06:00', 'close_at' => '20:00', 'is_closed' => false],
            '5' => ['name' => 'Saturday', 'open_at' => '08:00', 'close_at' => '16:00', 'is_closed' => false],
            '6' => ['name' => 'Sunday', 'open_at' => '08:00', 'close_at' => '16:00', 'is_closed' => false],
        ]);
    }
};
