<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up():void
    {
        Schema::table('material_week_activity', function (Blueprint $table) {
            $table->integer('quantity')->nullable()->default(null)->change();
            $table->string('description')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('material_week_activity', function (Blueprint $table) {
            $table->integer('quantity')->default(1)->change();
            $table->string('description')->nullable(false)->change();
        });
    }
};