<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::create('admin_poa_visibility', function (Blueprint $table) {
            $table->id();

            // Quién ve (Unidad Administrativa - Viene de TH)
            $table->foreignId('th_administrative_unit_id')
                ->constrained('th_administrative_units')
                ->onDelete('cascade')
                ->name('fk_visibility_unit_id'); // Nombre corto para evitar errores de longitud

            // Qué ve (Rubro - Viene de Planificación/Investigación)
            $table->foreignId('rubro_id')
                ->constrained('rubros') // Asegúrate que tu tabla de rubros se llame así
                ->onDelete('cascade');

            $table->timestamps();

            // Evitar duplicados
            $table->unique(['th_administrative_unit_id', 'rubro_id'], 'unique_visibility_rule');
        });
    }

    public function down()
    {
        Schema::dropIfExists('admin_poa_visibility');
    }
};
