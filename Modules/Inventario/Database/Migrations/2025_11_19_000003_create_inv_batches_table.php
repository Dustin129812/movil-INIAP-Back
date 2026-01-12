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
        Schema::create('inv_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('inv_products');
            $table->string('batch_code'); // Código Lote Fabricante

            // Fechas Clave
            $table->date('entry_date'); // FECHA DE ENTRADA (Llegada a bodega)
            $table->date('expiration_date')->nullable(); // Caducidad

            // Costos y Cantidades
            $table->decimal('unit_cost', 12, 4); // Costo por unidad (ml/g)
            $table->decimal('initial_quantity', 12, 2);
            $table->decimal('current_quantity', 12, 2);

            $table->boolean('is_active')->default(true);
            $table->boolean('is_expired')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('inv_batches', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });
        Schema::dropIfExists('inv_batches');
    }
};
