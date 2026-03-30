<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transferencia.ensayos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('tipo');
            $table->string('estado')->default('Activo');

            $table->string('nombre_tecnologia');
            $table->string('tipo_tecnologia');

            $table->boolean('tiene_protocolo')->default(false);
            $table->boolean('aprobado_por_comite')->default(false);
            $table->date('fecha_aprobacion_protocolo')->nullable();
            $table->string('archivo_protocolo_path')->nullable();
            $table->string('archivo_informe_path')->nullable();

            $table->foreignId('acuerdo_id')->nullable()->constrained('transferencia.acuerdos')->nullOnDelete();
            $table->foreignId('location_id')->constrained('locations');
            $table->foreignId('producto_id')->nullable()->constrained('products');
            $table->foreignId('actividad_id')->nullable()->constrained('activities');
            $table->foreignId('libro_campo_id')->nullable()->constrained('produccion.libros_campo');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement('DROP TABLE IF EXISTS transferencia.ensayos CASCADE;');
    }
};
