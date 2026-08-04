<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up() {
        Schema::create('AgroDecide.visitas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ciclo_cultivo_id');
            $table->unsignedBigInteger('proyecto_id');
            $table->string('tecnico_nombre');
            $table->date('fecha_visita');
            $table->text('observaciones')->nullable();
            $table->text('recomendaciones')->nullable();
            $table->uuid('uuid_movil')->unique();
            $table->timestamps();

            $table->foreign('proyecto_id')->references('id')->on('AgroDecide.proyectos')->onDelete('cascade');
            $table->foreign('ciclo_cultivo_id')
                ->references('id')
                ->on('AgroDecide.ciclos_cultivo')
                ->onDelete('cascade');
        });
    }

    public function down() {
        Schema::dropIfExists('AgroDecide.visitas');
    }
};
