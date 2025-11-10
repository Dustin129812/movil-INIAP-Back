<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registro_horas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // El Conductor
            $table->foreignId('vehiculo_id')->constrained('vehiculos'); // La placa

            $table->date('fecha'); // Fecha del registro
            $table->decimal('horas_suplementarias', 5, 2)->default(0);
            $table->decimal('horas_extraordinarias', 5, 2)->default(0);
            $table->text('descripcion_actividad');

            // Requisito: Límite de 2 días para registrar
            $table->timestamp('fecha_limite_registro');

            // Estados para el flujo de aprobación
            $table->string('estado', 50)->default('registrado'); // ej: registrado, aprobado_jefe, rechazado_jefe, aprobado_daf

            // Auditoría de aprobación/rechazo
            $table->foreignId('aprobador_jefe_id')->nullable()->constrained('users');
            $table->timestamp('aprobado_jefe_at')->nullable();
            $table->text('rechazo_jefe_motivo')->nullable();

            $table->foreignId('aprobador_daf_id')->nullable()->constrained('users');
            $table->timestamp('aprobado_daf_at')->nullable();
            $table->text('rechazo_daf_motivo')->nullable();

            $table->timestamps();
            $table->foreignId('reporte_mensual_he_id')->nullable()->constrained('reporte_mensual_hes');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registro_horas');
    }
};
