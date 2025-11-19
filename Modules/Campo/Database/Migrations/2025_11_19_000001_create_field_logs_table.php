<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. EL REGISTRO PRINCIPAL (Cabecera)
        Schema::create('field_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('week_activity_id')->constrained('weekly_activities');

            $table->date('execution_date'); // Cuándo se hizo realmente
            $table->decimal('duration_hours', 8, 2); // Duración real (para calcular mano de obra y uso de máquinas)
            $table->string('location_name')->nullable(); // La ubicación simple que pediste

            // COSTOS HISTÓRICOS (Se guardan calculados para que no cambien en el futuro)
            $table->decimal('labor_cost', 10, 2)->default(0);      // (RMU / 240) * Horas
            $table->decimal('machinery_cost', 10, 2)->default(0);  // Depreciación * Horas
            $table->decimal('input_cost', 10, 2)->default(0);      // Costo Lote * Cantidad
            $table->decimal('total_cost', 10, 2)->default(0);      // La suma de todo

            $table->text('observations')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. DETALLE DE INSUMOS USADOS (Descuenta de Lotes)
        Schema::create('field_log_inputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_log_id')->constrained('field_logs')->onDelete('cascade');

            // Relación con el módulo de Inventario
            $table->foreignId('batch_id')->constrained('inv_batches');

            $table->decimal('quantity_used', 12, 2);
            $table->decimal('unit_cost_snapshot', 10, 4); // Guardamos cuánto costaba ese lote ese día
            $table->decimal('total_line_cost', 10, 2);
            $table->timestamps();
        });

        // 3. DETALLE DE MAQUINARIA USADA
        Schema::create('field_log_machinery', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_log_id')->constrained('field_logs')->onDelete('cascade');

            // Relación con módulo de Inventario
            $table->foreignId('machinery_id')->constrained('inv_machinery');

            $table->decimal('hours_used', 8, 2); // Por defecto igual a la duración de la labor, pero editable
            $table->decimal('hourly_cost_snapshot', 10, 4); // El valor de depreciación ($0.02, etc.)
            $table->decimal('total_line_cost', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_log_machinery');
        Schema::dropIfExists('field_log_inputs');
        Schema::dropIfExists('field_logs');
    }
};
