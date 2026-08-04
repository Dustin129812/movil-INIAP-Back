<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('trl.matriz_trl', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_tecnologia', 100);
            $table->integer('nivel_trl');
            $table->text('criterio_texto');
            $table->boolean('es_critico')->default(true);
            $table->timestamps();

            $table->index(['tipo_tecnologia', 'nivel_trl']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('trl.matriz_trl');
    }
};
