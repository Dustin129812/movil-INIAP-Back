<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Arreglamos la tabla cantons
        Schema::table('cantons', function (Blueprint $table) {
            if (!Schema::hasColumn('cantons', 'provincia_id')) {
                $table->foreignId('provincia_id')
                    ->nullable()
                    ->constrained('provinces')
                    ->nullOnDelete();
            }
        });

        // 2. Arreglamos la tabla parroquias (por el bug de la doble declaración que tenías)
        Schema::table('parroquias', function (Blueprint $table) {
            if (!Schema::hasColumn('parroquias', 'canton_id')) {
                $table->foreignId('canton_id')
                    ->nullable()
                    ->constrained('cantons')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        // En caso de rollback, quitamos las llaves foráneas y las columnas
        Schema::table('cantons', function (Blueprint $table) {
            if (Schema::hasColumn('cantons', 'provincia_id')) {
                $table->dropForeign(['provincie_id']);
                $table->dropColumn('provincia_id');
            }
        });

        Schema::table('parroquias', function (Blueprint $table) {
            if (Schema::hasColumn('parroquias', 'canton_id')) {
                $table->dropForeign(['canton_id']);
                $table->dropColumn('canton_id');
            }
        });
    }
};
