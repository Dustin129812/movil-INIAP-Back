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
        Schema::create('trl.evaluaciones', function (Blueprint $table) {
            $table->string('id', 100)->primary(); // Se mantiene string porque viene del móvil (EVAL-...)

            // CAMBIO AQUÍ: Debe ser uuid() para machacar con el id de tecnologias
            $table->uuid('tecnologia_id');

            $table->dateTime('fecha');
            $table->string('tecnico', 150);
            $table->string('estado', 50)->default('finalizado');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Ahora sí coinciden los tipos (UUID con UUID)
            $table->foreign('tecnologia_id')
                ->references('id')
                ->on('trl.tecnologias')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trl.evaluaciones');
    }
};
