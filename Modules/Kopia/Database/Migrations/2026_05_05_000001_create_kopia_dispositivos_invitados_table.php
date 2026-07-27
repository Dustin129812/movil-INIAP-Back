<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up() {
        Schema::create('kopia.dispositivos_invitados', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('modelo_dispositivo')->nullable();
            $table->string('estado')->default('activo');
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('kopia.dispositivos_invitados');
    }
};
