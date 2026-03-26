<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weekly_activities', function (Blueprint $table) {
            $table->string('activity_type', 20)
                ->default('tecnica')
                ->comment('Diferencia si la actividad es tecnica o administrativa');
        });
    }

    public function down(): void
    {
        Schema::table('weekly_activities', function (Blueprint $table) {
            $table->dropColumn('activity_type');
        });
    }
};
