<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transferencia.parcelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations');

            $table->foreignId('ensayo_id')->constrained('transferencia.ensayos');
            $table->foreignId('organizacion_id')->constrained('transferencia.organizaciones');
            $table->foreignId('acuerdo_id')->nullable()->constrained('transferencia.acuerdos');
            $table->foreignId('libro_campo_id')->nullable()->constrained('produccion.libros_campo');

            $table->string('nombre');
            $table->foreignId('provincia_id')->constrained('provinces');
            $table->foreignId('canton_id')->constrained('cantons');
            $table->foreignId('parroquia_id')->constrained('parroquias');
            $table->string('localidad')->nullable();

            $table->string('coordenada_x')->nullable();
            $table->string('coordenada_y')->nullable();

            $table->date('fecha_implementacion')->nullable();
            $table->date('fecha_finalizacion')->nullable();

            $table->enum('estado', [
                'Planificada',
                'Implementado',
                'Perdido',
                'Dado de baja',
                'Finalizado'
            ])->default('Planificada');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transferencia.parcelas');
    }
};
