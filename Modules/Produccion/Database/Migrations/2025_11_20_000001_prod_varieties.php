<?php

// create_prod_varieties_table.php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::create('prod_varieties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('scientific_name')->nullable();

            $table->enum('type', ['SEED', 'GRAFT', 'VEGETATIVE']);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('prod_varieties');
    }
};
