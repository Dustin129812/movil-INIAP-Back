<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Esta migración encontrará todas las actividades con el estado ambiguo 'rated'
     * y las actualizará al nuevo estado 'completed'.
     */
    public function up(): void
    {
        $cutoffDate = Carbon::now()->startOfDay();

        // CASO 1: Si era 'rated' y el porcentaje era > 0, ahora es 'completed'.
        DB::table('weekly_activities')
            ->where('status', 'rated')
            ->where('percentage', '>', 0)
            ->where('updated_at', '<', $cutoffDate)
            ->update(['status' => 'completed']);

        // CASO 2 (NUEVO): Si era 'rated' y el porcentaje era 0, ahora es 'not completed'.
        DB::table('weekly_activities')
            ->where('status', 'rated')
            ->where('percentage', '=', 0)
            ->where('updated_at', '<', $cutoffDate)
            ->update(['status' => 'not completed']);
    }

    public function down(): void
    {
        $cutoffDate = Carbon::now()->startOfDay();

        // Revierte TODOS los registros antiguos modificados a su estado original 'rated'.
        DB::table('weekly_activities')
            ->whereIn('status', ['completed', 'not completed'])
            ->where('updated_at', '<', $cutoffDate)
            ->update(['status' => 'rated']);
    }
};
