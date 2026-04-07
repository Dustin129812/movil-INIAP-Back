<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reusable_activities', function (Blueprint $table) {
            $table->string('activity_type')->default('tecnica')->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('reusable_activities', function (Blueprint $table) {
            $table->dropColumn('activity_type');
        });
    }
};
