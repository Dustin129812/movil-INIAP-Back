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
    public function up()
    {
        Schema::create('produccion.libros_campo', function (Blueprint $table) {
            $table->id();
            $table->uuid('qr_token')->nullable()->unique();
            
            $table->foreignId('lote_id')->constrained('produccion.lotes')->restrictOnDelete();
            $table->string('codigo', 50)->unique(); // Ej: LC-001-2026
            $table->string('nombre', 150);
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->string('estado', 20)->default('ABIERTO'); // ABIERTO, CERRADO, ANULADO
            $table->timestamps();
            $table->softDeletes();

            // -- PRODUCCIÓN - INGRESO --

            $table->decimal('cantidad_cosechada', 12, 4)->nullable();
            $table->foreignId('insumo_cosechado_id')->nullable()->constrained('produccion.insumos');
            $table->foreignId('kardex_ingreso_id')->nullable()->constrained('produccion.kardex');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('produccion.libros_campo');
    }
};
