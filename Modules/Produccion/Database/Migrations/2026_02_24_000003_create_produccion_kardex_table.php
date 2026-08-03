<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produccion.kardex', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bodega_id')->constrained('produccion.bodegas')->restrictOnDelete();
            $table->foreignId('insumo_id')->constrained('produccion.insumos')->restrictOnDelete();

            $table->enum('tipo_movimiento', ['INGRESO', 'EGRESO', 'AJUSTE']);
            $table->decimal('cantidad', 12, 4);
            $table->decimal('costo_unitario', 12, 4);
            $table->decimal('costo_total', 12, 4);

            $table->decimal('saldo_cantidad', 12, 4);
            $table->decimal('costo_promedio', 12, 4);

            $table->string('documento_referencia')->nullable();
            $table->text('observaciones')->nullable();

            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('produccion.kardex');
    }
};
