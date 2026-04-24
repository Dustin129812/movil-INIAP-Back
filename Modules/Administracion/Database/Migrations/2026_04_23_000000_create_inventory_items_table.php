<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administracion.inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);
            $table->string('name', 200);
            $table->string('sku', 100)->unique()->nullable();

            $table->jsonb('attributes')->nullable()->comment('Almacena propiedades específicas según el type (placa, agente_activo, etc.)');

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['attributes'], 'attributes_gin', 'gin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administracion.inventory_items');
    }
};
