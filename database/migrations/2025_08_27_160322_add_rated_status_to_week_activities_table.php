<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Elimina la restricción anterior SI EXISTE para evitar errores.
        DB::statement("ALTER TABLE weekly_activities DROP CONSTRAINT IF EXISTS weekly_activities_status_check");

        // 2. Cambia el tipo de la columna (si es necesario).
        DB::statement("ALTER TABLE weekly_activities ALTER COLUMN status TYPE VARCHAR(255)");

        // 3. Añade la nueva restricción con el nuevo estado 'rated'.
        DB::statement("ALTER TABLE weekly_activities ADD CONSTRAINT weekly_activities_status_check CHECK (status IN ('pending', 'approved', 'rejected', 'reassigned', 'rated'))");

        // 4. Asegúrate que el valor por defecto sigue siendo 'pending'.
        DB::statement("ALTER TABLE weekly_activities ALTER COLUMN status SET DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Para revertir, primero eliminamos la restricción actual.
        DB::statement("ALTER TABLE weekly_activities DROP CONSTRAINT IF EXISTS weekly_activities_status_check");

        // Y luego restauramos la restricción original sin 'rated'.
        DB::statement("ALTER TABLE weekly_activities ADD CONSTRAINT weekly_activities_status_check CHECK (status IN ('pending', 'approved', 'rejected', 'reassigned'))");
    }
};
