<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up() {
        Schema::create('AgroDecide.hojas_datos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('visita_id');
            $table->string('nombre_plantilla'); // Ej: 'Evaluación Amaranto'
            $table->jsonb('datos_variables'); // La matriz de filas y columnas
            $table->uuid('uuid_movil')->unique();
            $table->timestamps();

            $table->foreign('visita_id')
                ->references('id')
                ->on('AgroDecide.visitas')
                ->onDelete('cascade');
        });
    }

    public function down() {
        Schema::dropIfExists('AgroDecide.hojas_datos');
    }
};
