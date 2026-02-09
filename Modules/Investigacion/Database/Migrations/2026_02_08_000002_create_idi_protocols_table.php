<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idi_protocols', function (Blueprint $table) {
            $table->id();

            // 1. Identificación del Protocolo
            $table->string('project_name')->nullable(); // Nombre del proyecto padre
            $table->string('activity_title'); // Título de la actividad/protocolo

            // 2. Ubicación (Estación Experimental)
            $table->foreignId('station_id')->constrained('locations');

            // 3. Clasificación Técnica
            $table->foreignId('research_line_id')->constrained('research_lines');
            $table->foreignId('crop_id')->constrained('crops'); // Rubro y Cultivo

            // 4. TRL (Madurez Tecnológica)
            $table->integer('trl_current')->comment('Escala actual 1-9');
            $table->text('trl_justification')->nullable();
            $table->text('trl_supports')->nullable(); // Estudios que respaldan
            $table->integer('trl_target')->comment('Escala esperada');

            // 5. Responsable y Fechas
            $table->foreignId('responsible_id')->constrained('users'); // Responsable interno (User)
            $table->date('start_date');
            $table->date('end_date');

            // 6. Colaboradores Externos (Texto simple)
            $table->text('external_collaborators')->nullable()->comment('Nombres e instituciones de externos');

            // 7. Presupuesto y Financiamiento
            $table->string('funding_source'); // Fuente financiamiento
            $table->string('donor_name')->nullable(); // Nombre del donante
            $table->string('iniap_role'); // Rol INIAP (ej. Coejecutor)

            $table->decimal('budget_total', 15, 2);
            $table->decimal('external_amount', 15, 2)->default(0);
            $table->integer('external_percent')->default(0);
            $table->integer('iniap_percent')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idi_protocols');
    }
};
