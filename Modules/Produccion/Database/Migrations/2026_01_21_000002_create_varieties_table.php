<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up() {
        Schema::create('varieties', function (Blueprint $table) {
            $table->id();

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
