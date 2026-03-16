<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Unir Cantones con Provincias
        Schema::table('cantons', function (Blueprint $table) {
            $table->foreignId('provincia_id')
                ->nullable()
                ->constrained('provinces')
                ->nullOnDelete(); // Si borran la provincia, no borramos el cantón, solo lo desligamos
        });

        // 2. Unir Parroquias con Cantones
        Schema::table('parroquias', function (Blueprint $table) {
            $table->foreignId('canton_id')
                ->nullable()
                ->constrained('cantons')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('parroquias', function (Blueprint $table) {
            $table->dropForeign(['canton_id']);
            $table->dropColumn('canton_id');
        });

        Schema::table('cantons', function (Blueprint $table) {
            $table->dropForeign(['provincia_id']);
            $table->dropColumn('provincia_id');
        });
    }
};
