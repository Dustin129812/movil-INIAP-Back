<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up() {
        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('surface', 10, 2);
            $table->string('location');
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('lots');
    }
};
