<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispositivos', function (Blueprint $table) {

            $table->id();

            // Relación con usuarios
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            // UUID único del dispositivo móvil
            $table->string('uuid')->unique();

            // Información del dispositivo
            $table->string('modelo')->nullable();
            $table->string('sistema_operativo')->nullable();

            // Última vez que inició sesión
            $table->timestamp('ultimo_login')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispositivos');
    }
};