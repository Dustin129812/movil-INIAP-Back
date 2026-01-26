<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

// 2025_03_xx_create_production_plans_table.php
return new class extends Migration {
    public function up() {
        Schema::create('production_plans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('variety_id')->constrained('varieties');
            $table->foreignId('lot_id')->constrained('lots');

            $table->decimal('seed_quantity', 12, 2);
            $table->foreignId('seed_category_id')->constrained('categories');

            $table->decimal('expected_quantity', 12, 2);
            $table->string('unit_of_measure');

            $table->string('expense_type');
            $table->text('observation')->nullable();

            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('production_plans');
    }
};
