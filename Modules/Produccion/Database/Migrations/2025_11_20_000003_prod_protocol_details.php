<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::create('prod_protocol_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('protocol_id')->constrained('prod_protocols')->onDelete('cascade');

            // CRONOGRAMA SUGERIDO (Días del ciclo)
            $table->integer('day_start'); // Día 1
            $table->integer('day_end')->nullable(); // Día 4

            // DESCRIPCIÓN DE LA TAREA (Columnas A y B del Excel)
            $table->string('stage'); // Ej: "1. Adquisición de insumos"
            $table->string('task');  // Ej: "Desinfección de semilla"

            // TIPO DE RECURSO
            // PRODUCT = Insumo de Inventario (Fertilizante)
            // MACHINERY = Activo Fijo (Bomba, Tractor)
            // LABOR = Mano de obra (Jornal, Técnico)
            $table->enum('resource_type', ['PRODUCT', 'MACHINERY', 'LABOR', 'SERVICE']);

            // CONEXIÓN CON MÓDULO INVENTARIO (Nullables porque Labor no tiene ID de inventario)
            $table->foreignId('inv_product_id')->nullable()->constrained('inv_products');
            $table->foreignId('inv_machinery_id')->nullable()->constrained('inv_machinery');

            // Para Mano de Obra o Servicios externos que no están en inventario
            $table->string('resource_name')->nullable();

            // CANTIDADES TEÓRICAS (Por cada 'base_quantity' del protocolo)
            // Ej: Si base es 10k plantas, aquí pones 0.5 Litros.
            $table->decimal('quantity', 12, 4);

            // COSTO REFERENCIAL (Para proyecciones financieras antes de ejecutar)
            $table->decimal('reference_unit_cost', 12, 4);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::table('prod_protocol_details', function (Blueprint $table) {
            $table->dropForeign(['inv_product_id']);
            $table->dropForeign(['inv_machinery_id']);
        });
        Schema::dropIfExists('prod_protocol_details');
    }
};
