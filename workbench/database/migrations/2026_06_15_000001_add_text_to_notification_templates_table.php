<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('notification_templates', 'text')) {
            Schema::table('notification_templates', function (Blueprint $table) {
                $table->text('text')->nullable();
            });
        }
    }

    public function down()
    {
        Schema::table('notification_templates', function (Blueprint $table) {
            $table->dropColumn('text');
        });
    }
};
