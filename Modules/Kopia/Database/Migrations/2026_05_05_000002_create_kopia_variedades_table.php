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
        Schema::create('kopia.variedades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cultivo_id');
            $table->string('nombre');
            $table->jsonb('caracteristicas_base')->nullable();
            $table->timestamps();

            $table->foreign('cultivo_id')->references('id')->on('kopia.cultivos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kopia.variedades');
    }
};
