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
        Schema::create('produccion.actividades_personal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('libro_campo_id')->constrained('produccion.libros_campo')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('public.users');

            $table->date('fecha');
            $table->string('labor', 200);
            $table->decimal('horas_trabajadas', 8, 2);
            $table->decimal('costo_hora', 12, 4);
            $table->decimal('costo_total', 12, 4);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('produccion.actividades_personal');
    }
};
