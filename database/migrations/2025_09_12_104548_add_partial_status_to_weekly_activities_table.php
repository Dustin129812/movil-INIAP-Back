<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * El nombre de la restricción CHECK que encontraste en el paso anterior.
     */
    private $constraintName = 'weekly_activities_status_check'; // <-- ¡REEMPLAZA ESTO SI ES NECESARIO!

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Lista completa de los estados, incluyendo el nuevo 'partial'
        $allowedStatuses = "('pending', 'approved', 'rejected', 'in progress', 'completed', 'rated', 'reassigned', 'partial', 'not completed')";

        // 1. Eliminamos la restricción CHECK existente
        DB::statement("ALTER TABLE weekly_activities DROP CONSTRAINT {$this->constraintName}");

        // 2. Añadimos una nueva restricción CHECK con la lista actualizada de valores
        DB::statement("ALTER TABLE weekly_activities ADD CONSTRAINT {$this->constraintName} CHECK (status IN {$allowedStatuses})");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Lista de estados original, sin 'partial', para poder revertir
        $originalStatuses = "('pending', 'approved', 'rejected', 'in progress', 'completed', 'rated', 'reassigned', 'not completed')";

        // 1. Elimina la restricción que añadimos
        DB::statement("ALTER TABLE weekly_activities DROP CONSTRAINT {$this->constraintName}");

        // 2. Vuelve a crear la restricción original
        DB::statement("ALTER TABLE weekly_activities ADD CONSTRAINT {$this->constraintName} CHECK (status IN {$originalStatuses})");
    }
};
