<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produccion.unidades_medida', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50); // Ej: Kilogramo
            $table->string('abreviatura', 10); // Ej: KG
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('produccion.unidades_medida');
    }
};
