<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Paso 1: Crear la tabla pivote 'activity_indicator'.
        Schema::create('activity_indicator', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->onDelete('cascade');

            $table->foreignId('indicator_id')->constrained('performance_indicators')->onDelete('cascade');

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
        // Este método revierte los cambios.

        // Paso 1: Volver a añadir la columna 'indicator_id' a la tabla 'activities'.
        Schema::table('activities', function (Blueprint $table) {
            // CORRECCIÓN: Se especifica el nombre de la tabla en singular ('indicator') también aquí.
            $table->foreignId('indicator_id')->nullable()->constrained('performance_indicators');
        });

        // Paso 2: Eliminar la tabla pivote.
        Schema::dropIfExists('activity_indicator');
    }
};