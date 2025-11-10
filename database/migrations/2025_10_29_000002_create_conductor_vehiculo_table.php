<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conductor_vehiculo', function (Blueprint $table) {
            $table->id();
            // Usamos constrained() y cascadeOnDelete() para integridad referencial
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->cascadeOnDelete();
            $table->timestamps();

            // Evita duplicados (mismo usuario con mismo vehículo)
            $table->unique(['user_id', 'vehiculo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conductor_vehiculo');
    }
};
