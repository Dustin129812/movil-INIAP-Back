<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up() {
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
        DB::statement('CREATE SCHEMA IF NOT EXISTS "AgroDecide";');
    }

    public function down() {
        DB::statement('DROP SCHEMA IF EXISTS "AgroDecide" CASCADE');
    }
};
