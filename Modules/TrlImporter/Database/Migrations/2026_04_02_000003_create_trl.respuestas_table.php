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
        Schema::create('trl.respuestas', function (Blueprint $table) {
            $table->string('id', 100)->primary();
            $table->string('evaluacion_id', 100);

            // Si matriz_trl usa id autoincremental normal:
            $table->unsignedBigInteger('matriz_trl_id');

            $table->boolean('cumple')->default(false);
            $table->timestamps();

            $table->foreign('evaluacion_id')->references('id')->on('trl.evaluaciones')->onDelete('cascade');
            $table->foreign('matriz_trl_id')->references('id')->on('trl.matriz_trl')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trl.respuestas');
    }
};
