<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up() {
        Schema::create('AgroDecide.dispositivos_invitados', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('modelo_dispositivo')->nullable();
            $table->string('sistema_operativo', 100)->nullable();
            $table->string('hardware', 255)->nullable();
            $table->string('estado')->default('activo');
            $table->timestamp('ultimo_login')->nullable();
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('AgroDecide.dispositivos_invitados');
    }
};
