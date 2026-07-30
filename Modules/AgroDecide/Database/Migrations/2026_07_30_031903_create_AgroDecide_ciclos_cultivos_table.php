<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up() {
        Schema::create('AgroDecide.ciclos_cultivo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lote_id');
            $table->unsignedBigInteger('proyecto_id')->nullable();
            $table->string('cultivo_variedad');
            $table->string('distancia_siembra');
            $table->date('fecha_siembra');
            $table->date('fecha_fin')->nullable();
            $table->jsonb('metricas_siembra')->nullable();
            $table->boolean('es_actual')->default(true);
            $table->uuid('uuid_movil')->unique();
            $table->timestamps();

            $table->foreign('lote_id')
                ->references('id')
                ->on('AgroDecide.lotes')
                ->onDelete('cascade');

            $table->foreign('proyecto_id')
                ->references('id')
                ->on('AgroDecide.proyectos')
                ->onDelete('cascade');
        });
    }

    public function down() {
        Schema::dropIfExists('AgroDecide.ciclos_cultivo');
    }
};
