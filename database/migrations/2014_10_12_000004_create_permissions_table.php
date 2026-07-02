<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        // No-op: permissions table is deprecated in favor of storing scope directly on permissionables
    }
};
