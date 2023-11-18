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
            'app_name' => config('app.name') ?? 'Company Name',
            'app_timezone' => "Asia/Calcutta",
            'phone_number' => "+9733014543",
            'app_email' => "hello@company.com",
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
