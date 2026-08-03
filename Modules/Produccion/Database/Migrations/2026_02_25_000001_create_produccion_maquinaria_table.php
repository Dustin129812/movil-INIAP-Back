<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('produccion.maquinaria', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('placa_serie', 50)->nullable();
            $table->decimal('costo_hora', 12, 4);
            $table->string('estado', 20)->default('OPERATIVO');
            $table->timestamps();
            $table->softDeletes();
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('produccion.maquinaria');
    }
};
