<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('produccion.actividades_maquinaria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('libro_campo_id')->constrained('produccion.libros_campo')->cascadeOnDelete();
            $table->foreignId('maquinaria_id')->constrained('produccion.maquinaria')->restrictOnDelete();
            $table->date('fecha');
            $table->decimal('horas_uso', 8, 2);
            $table->decimal('costo_total', 12, 4);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('produccion.actividades_maquinaria');
    }
};
