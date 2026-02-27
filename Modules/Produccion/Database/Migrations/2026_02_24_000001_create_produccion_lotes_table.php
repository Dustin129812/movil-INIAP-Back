<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produccion.lotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations');
            $table->foreignId('parent_id')->nullable()->constrained('produccion.lotes')->onDelete('cascade');

            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->decimal('superficie_hectareas', 12, 4);
            $table->enum('estado', ['PREPARACION', 'PRODUCCION', 'CERRADO'])->default('PREPARACION');

            $table->geometry('poligono', 'polygon', 4326)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produccion.lotes');
    }
};
