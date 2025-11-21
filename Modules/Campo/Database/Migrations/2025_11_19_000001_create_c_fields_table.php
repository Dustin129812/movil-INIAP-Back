<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('p_fields', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ej: "Lote Norte - Sector Río"
            $table->decimal('area_hectares', 8, 2); // Para calcular rendimiento/ha
            $table->string('current_crop')->nullable(); // Ej: "Cacao"
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('p_fields');
    }
};
