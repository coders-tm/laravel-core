<?php

use Coderstm\Models\File;
use Coderstm\Models\AppSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
        AppSetting::create('config', [
            'lang' => 'en-US',
            'name' => config('app.name', 'Company Name'),
            'country' => "India",
            'timezone' => config('app.timezone', "Asia/Calcutta"),
            'phone' => "+9733014543",
            'email' => config('coderstm.admin_email', "email@change.me"),
            'currency' => config('cashier.currency', 'usd'),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
