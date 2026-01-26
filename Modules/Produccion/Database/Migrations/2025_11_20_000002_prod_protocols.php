<?php

// create_prod_protocols_table.php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::create('prod_protocols', function (Blueprint $table) {
            $table->id();
            //$table->foreignId('variety_id')->constrained('prod_varieties'); // Relación con Naranjilla/Durazno

            $table->string('name'); // Ej: "Protocolo Estándar 2022"
            $table->text('description')->nullable();

            // DATOS DEL EXCEL PARA CÁLCULOS
            $table->integer('base_quantity')->default(10000); // Tu Excel calcula todo en base a 10k plantas
            $table->integer('estimated_days'); // Ej: 140 días

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('prod_protocols');
        Schema::enableForeignKeyConstraints();
    }
};
