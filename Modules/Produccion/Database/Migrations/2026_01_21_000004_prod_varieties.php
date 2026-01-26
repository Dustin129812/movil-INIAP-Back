<?php

// create_prod_varieties_table.php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

// 2025_03_xx_create_varieties_table.php
return new class extends Migration {
    public function up() {
        Schema::create('varieties', function (Blueprint $table) {
            $table->id();

            // Relaciones solicitadas
            $table->foreignId('productive_rubro_id')->constrained('productive_rubros');
            $table->foreignId('crop_id')->constrained('crops');
            $table->foreignId('category_id')->constrained('categories');
            $table->foreignId('variety_type_id')->constrained('variety_types'); // El tipo (id)

            $table->string('name');

            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('varieties');
    }
};