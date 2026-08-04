<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up() {
        Schema::create('kopia.proyecto_colaborador', function (Blueprint $table) {
            $table->unsignedBigInteger('proyecto_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->primary(['proyecto_id', 'user_id']);
            $table->foreign('proyecto_id')->references('id')->on('kopia.proyectos')->onDelete('cascade');

            $table->foreign('user_id')->references('id')->on('public.users')->onDelete('cascade');
        });
    }

    public function down() {
        Schema::dropIfExists('kopia.proyecto_colaborador');
    }
};
