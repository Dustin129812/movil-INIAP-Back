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
            // Llave foránea para la actividad (asume que la tabla es 'activities')
            $table->foreignId('activity_id')->constrained('activities')->onDelete('cascade');

            // CORRECCIÓN: Se especifica el nombre de la tabla en singular ('indicator').
            // Asegúrate de que 'indicator' sea el nombre exacto de tu tabla de indicadores.
            $table->foreignId('indicator_id')->constrained('performance_indicators')->onDelete('cascade');

            $table->timestamps();
        });

        // Paso 2: Modificar la tabla 'activities' para eliminar la antigua columna 'indicator_id'.
        Schema::table('activities', function (Blueprint $table) {
            // Se asume que el nombre de la restricción de llave foránea es 'activities_indicator_id_foreign'.
            // Si tu restricción tiene un nombre diferente, puedes especificarlo así:
            // $table->dropForeign('nombre_de_la_restriccion');
            $table->dropForeign(['indicator_id']);

            // Finalmente, eliminamos la columna que ya no es necesaria.
            $table->dropColumn('indicator_id');
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
