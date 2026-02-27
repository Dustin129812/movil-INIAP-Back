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
        Schema::create('produccion.actividades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('libro_campo_id')->constrained('produccion.libros_campo')->cascadeOnDelete();

            $table->foreignId('kardex_id')->nullable()->constrained('produccion.kardex')->nullOnDelete();

            $table->date('fecha');
            $table->string('labor', 200);
            $table->decimal('cantidad_insumo', 12, 4)->default(0);
            $table->decimal('costo_actividad', 12, 4)->default(0);

            $table->text('observaciones')->nullable();
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
        Schema::dropIfExists('produccion.actividades');
    }
};
