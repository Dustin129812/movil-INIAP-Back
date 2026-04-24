<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administracion.warehouse_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('warehouse_id')
                ->constrained('administracion.warehouses')
                ->onDelete('cascade');

            $table->foreignId('inventory_item_id')
                ->constrained('administracion.inventory_items')
                ->onDelete('restrict');

            $table->decimal('stock', 10, 2)->default(0);
            $table->decimal('min_stock', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(['warehouse_id', 'inventory_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administracion.warehouse_items');
    }
};
