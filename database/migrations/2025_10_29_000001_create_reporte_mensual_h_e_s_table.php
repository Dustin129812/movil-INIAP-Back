<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Esta tabla almacena el "resumen" mensual que se aprueba.
        Schema::create('reporte_mensual_hes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users'); // El Conductor
            $table->integer('mes');
            $table->integer('anio');

            $table->decimal('total_horas_suplementarias', 8, 2);
            $table->decimal('total_horas_extraordinarias', 8, 2);
            $table->decimal('monto_suplementarias', 10, 2);
            $table->decimal('monto_extraordinarias', 10, 2);
            $table->decimal('monto_fondos_reserva', 10, 2);
            $table->decimal('monto_decimo_tercero', 10, 2);
            $table->decimal('monto_total_pagar', 10, 2);

            // Flujo de aprobación del reporte (Vladimir -> Majo)
            $table->string('estado', 50)->default('pendiente_jefe'); // ej: pendiente_jefe, pendiente_daf, aprobado, pagado

            // Auditoría
            $table->foreignId('jefe_id')->nullable()->constrained('users'); // Vladimir
            $table->timestamp('aprobado_jefe_at')->nullable();
            $table->foreignId('daf_id')->nullable()->constrained('users'); // Majo
            $table->timestamp('aprobado_daf_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Asegura que solo haya un reporte por usuario al mes/año
            $table->unique(['user_id', 'mes', 'anio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reporte_mensual_hes');
    }
};
