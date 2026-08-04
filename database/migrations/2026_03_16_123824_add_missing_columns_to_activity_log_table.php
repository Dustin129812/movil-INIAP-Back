<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            // Agregamos uuid soportado nativamente por PostgreSQL
            if (!Schema::hasColumn('activity_log', 'batch_uuid')) {
                $table->uuid('batch_uuid')->nullable()->after('properties');
            }

            // Prevenimos otro posible error de la v4 de Spatie
            if (!Schema::hasColumn('activity_log', 'event')) {
                $table->string('event')->nullable()->after('subject_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropColumn(['batch_uuid', 'event']);
        });
    }
};
