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
        Schema::create('kopia.proyecto_variedad', function (Blueprint $table) {
            $table->unsignedBigInteger('proyecto_id');
            $table->unsignedBigInteger('variedad_id');
            $table->timestamps();

            $table->primary(['proyecto_id', 'variedad_id']);
            $table->foreign('proyecto_id')->references('id')->on('kopia.proyectos')->onDelete('cascade');
            $table->foreign('variedad_id')->references('id')->on('kopia.variedades')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kopia.proyecto_variedad');
    }
};
