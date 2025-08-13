<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * El nombre de la restricción CHECK. Reemplázalo si es diferente.
     */
    protected $constraintName = 'weekly_activities_status_check';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Eliminamos la restricción CHECK existente
        DB::statement("ALTER TABLE weekly_activities DROP CONSTRAINT {$this->constraintName}");

        // 2. Añadimos una nueva restricción CHECK con el valor 'reassigned'
        DB::statement("
            ALTER TABLE weekly_activities
            ADD CONSTRAINT {$this->constraintName}
            CHECK (status IN ('pending', 'approved', 'rejected', 'in progress', 'completed', 'reassigned'))
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Eliminamos la nueva restricción CHECK
        DB::statement("ALTER TABLE weekly_activities DROP CONSTRAINT {$this->constraintName}");

        // 2. Re-añadimos la restricción CHECK original (sin 'reassigned')
        DB::statement("
            ALTER TABLE weekly_activities
            ADD CONSTRAINT {$this->constraintName}
            CHECK (status IN ('pending', 'approved', 'rejected', 'in progress', 'completed'))
        ");
    }
};
