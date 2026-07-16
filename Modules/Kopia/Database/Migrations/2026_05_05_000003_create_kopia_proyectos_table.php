<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up() {
        Schema::create('kopia.proyectos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid_movil')->unique();
            $table->unsignedBigInteger('lote_id');
            $table->unsignedBigInteger('responsable_id');

            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->jsonb('objetivos')->nullable();
            $table->jsonb('informacion_adicional')->nullable();

            $table->string('variedad')->comment('Campo de digitación abierta');
            $table->date('fecha_siembra')->nullable();

            $table->string('tipo_acolchado', 30)->nullable()
                ->comment('Admite: con_acolchado, parcialmente_acolchado, sin_acolchado');

            $table->enum('diseno_experimental', ['con_diseno', 'sin_diseno', 'multiplicacion'])->nullable();

            $table->enum('tipo_ensayo', [
                'investigacion',
                'validacion',
                'produccion_semillas',
                'multiplicacion_semillas',
                'refrescamiento'
            ])->nullable();

            $table->string('financiamiento')->nullable();
            $table->string('colaborador_nombre')->nullable();
            $table->string('colaborador_telefono')->nullable();
            $table->string('colaborador_celular')->nullable();

            $table->timestamps();

            $table->foreign('lote_id')->references('id')->on('kopia.lotes')
                ->onDelete('restrict');
            $table->foreign('responsable_id')->references('id')->on('users')
                ->onDelete('restrict');
        });
    }

    public function down() {
        Schema::dropIfExists('kopia.proyectos');
    }
};
