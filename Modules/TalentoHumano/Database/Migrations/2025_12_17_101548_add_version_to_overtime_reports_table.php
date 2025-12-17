<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('th_overtime_reports', function (Blueprint $table) {
            $table->integer('version')->default(1)->after('year');

            $table->dropUnique(['driver_id', 'month', 'year']);
            $table->unique(['driver_id', 'month', 'year', 'version']);
        });
    }

    public function down(): void
    {
        Schema::table('th_overtime_reports', function (Blueprint $table) {
            $table->dropUnique(['driver_id', 'month', 'year', 'version']);
            $table->dropColumn('version');
            $table->unique(['driver_id', 'month', 'year']);
        });
    }
};
