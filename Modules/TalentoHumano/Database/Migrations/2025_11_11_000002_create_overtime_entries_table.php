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
        Schema::create('th_overtime_reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('driver_id')->constrained('users');

            $table->integer('month');
            $table->integer('year');
            $table->integer('version')->default(1);

            $table->string('status')->default('borrador'); // borrador, pendiente_supervisor, pendiente_daf, pendiente_dath, aprobado, rechazado
            $table->text('rejection_reason')->nullable(); // Razón si se rechaza
            $table->foreignId('supervisor_approver_id')->nullable()->constrained('users'); // Quién lo aprobó (Supervisor)
            $table->foreignId('daf_approver_id')->nullable()->constrained('users'); // Quién lo aprobó (DAF)
            $table->timestamp('submitted_at')->nullable(); // Cuándo lo envió el conductor
            $table->timestamp('supervisor_approved_at')->nullable();
            $table->timestamp('daf_approved_at')->nullable();

            $table->decimal('rmu_at_submission', 8, 2); // Copia del RMU al momento de enviar
            $table->decimal('hour_value', 8, 4); // RMU / 240

            $table->integer('total_supplemental_minutes')->default(0); // Horas al 1.5x
            $table->integer('total_extraordinary_minutes')->default(0); // Horas al 2.0x

            $table->decimal('total_supplemental_usd', 10, 2)->default(0);
            $table->decimal('total_extraordinary_usd', 10, 2)->default(0);

            $table->decimal('decimo_tercero_usd', 10, 2)->default(0);
            $table->decimal('fondos_reserva_usd', 10, 2)->default(0);
            $table->decimal('total_usd_pay', 10, 2)->default(0); // El gran total a pagar

            $table->timestamps();

            $table->unique(['driver_id', 'month', 'year', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('th_overtime_reports');
    }
};
