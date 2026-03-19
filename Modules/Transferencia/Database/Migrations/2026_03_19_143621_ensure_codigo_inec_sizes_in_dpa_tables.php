<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ==========================================
        // 1. PROVINCIAS (Debe ser VARCHAR 2)
        // ==========================================
        if (!Schema::hasColumn('provinces', 'codigo_inec')) {
            Schema::table('provinces', function (Blueprint $table) {
                $table->string('codigo_inec', 10)->nullable()->unique()->comment('Código DPA_PROVIN (2 dígitos)');
            });
        } else {
            // Si existe, blindamos su tamaño en la base de datos sin tocar la data
            DB::statement('ALTER TABLE provinces ALTER COLUMN codigo_inec TYPE VARCHAR(2);');
        }

        // ==========================================
        // 2. CANTONES (Debe ser VARCHAR 4)
        // ==========================================
        if (!Schema::hasColumn('cantons', 'codigo_inec')) {
            Schema::table('cantons', function (Blueprint $table) {
                $table->string('codigo_inec', 10)->nullable()->unique()->comment('Código DPA_CANTON (4 dígitos)');
            });
        } else {
            DB::statement('ALTER TABLE cantons ALTER COLUMN codigo_inec TYPE VARCHAR(4);');
        }

        // ==========================================
        // 3. PARROQUIAS (Debe ser VARCHAR 6)
        // ==========================================
        if (!Schema::hasColumn('parroquias', 'codigo_inec')) {
            Schema::table('parroquias', function (Blueprint $table) {
                $table->string('codigo_inec', 10)->nullable()->unique()->comment('Código DPA_PARROQ (6 dígitos)');
            });
        } else {
            DB::statement('ALTER TABLE parroquias ALTER COLUMN codigo_inec TYPE VARCHAR(6);');
        }
    }

    public function down(): void
    {
        // En migraciones de saneamiento/idempotentes orientadas a producción,
        // el método down suele dejarse vacío o solo hacer log, para evitar
        // que un rollback accidental trunque (corte) datos reales.
    }
};
