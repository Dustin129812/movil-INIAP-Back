<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Crear el esquema de PostgreSQL
        DB::statement('CREATE SCHEMA IF NOT EXISTS investigaciones;');

        // 2. Crear la tabla principal
        Schema::create('investigaciones.idi_protocols', function (Blueprint $table) {
            $table->id();

            // Step 1: Identificación
            $table->string('activity_title');
            $table->string('project_name')->nullable();
            $table->foreignId('station_id')->constrained('locations');

            // Step 2: Técnico
            $table->foreignId('research_line_id')->constrained('research_lines');
            $table->foreignId('crop_id')->constrained('crops');
            $table->integer('trl_current');
            $table->integer('trl_target');

            // Step 3: Cronograma y Equipo
            $table->date('start_date');
            $table->date('end_date');
            $table->foreignId('responsible_id')->constrained('users');
            $table->text('external_collaborators')->nullable();

            // Step 4: Presupuesto
            $table->string('funding_source', 100);
            $table->string('iniap_role', 100);
            $table->string('donor_name')->nullable();
            $table->decimal('budget_total', 12, 2)->default(0);
            $table->decimal('external_amount', 12, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investigaciones.idi_protocols');
    }
};
