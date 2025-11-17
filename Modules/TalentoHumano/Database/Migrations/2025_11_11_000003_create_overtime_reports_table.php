<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('th_overtime_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('overtime_report_id')
                ->constrained('th_overtime_reports')
                ->onDelete('cascade'); // Si se borra el reporte, se borran sus entradas

            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration_minutes'); // Calculado automáticamente

            $table->foreignId('activity_type_id')->constrained('th_activity_types');
            $table->string('vehicle_placa')->constrained('th_vehicles');

            $table->text('observations')->nullable(); // "DR. JARAMILLO"

            $table->integer('supplemental_minutes')->default(0);
            $table->integer('extraordinary_minutes')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('th_overtime_entries');
    }
};
