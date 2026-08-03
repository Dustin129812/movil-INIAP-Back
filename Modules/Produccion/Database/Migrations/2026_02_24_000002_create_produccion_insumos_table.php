<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produccion.insumos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unidad_medida_id')->constrained('produccion.unidades_medida')->restrictOnDelete();
            $table->string('tipo', 50);
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('produccion.insumos');
    }
};
