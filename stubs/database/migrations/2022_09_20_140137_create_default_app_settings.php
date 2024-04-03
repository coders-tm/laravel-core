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

        AppSetting::create('config', [
            'lang' => 'en-US',
            'name' => config('app.name', 'Company Name'),
            'country' => "India",
            'timezone' => config('app.timezone', "Asia/Calcutta"),
            'phone' => "+9733014543",
            'email' => config('coderstm.admin_email', "email@change.me"),
            'currency' => config('cashier.currency', 'usd'),
        ]);

        AppSetting::create('mail', [
            'default' => 'smtp',
            'mailers.smtp.host' => config('mail.mailers.smtp.host', '127.0.0.1'),
            'mailers.smtp.port' => config('mail.mailers.smtp.port', '1025'),
            'mailers.smtp.encryption' => config('mail.mailers.smtp.encryption', null),
            'mailers.smtp.username' => config('mail.mailers.smtp.username', null),
            'mailers.smtp.password' => config('mail.mailers.smtp.password', null),
        ]);

        AppSetting::create('address', [
            'company' => config('app.name'),
            'line1' => 'Address Line 1',
            'line2' => '',
            'city' => 'North 24 Pargans',
            'state' => 'West Bengal',
            'state_code' => 'WB',
            'country' => 'India',
            'country_code' => 'IN',
            'postal_code' => '743273',
        ]);

        AppSetting::create('theme', [
            'primary' => null
        ]);

        AppSetting::create('alert', [
            'whatsapp' => false,
            'push' => false,
        ]);
    }
};
