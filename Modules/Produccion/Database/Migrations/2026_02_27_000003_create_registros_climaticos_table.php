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
        Schema::create('produccion.registros_climaticos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('libro_campo_id')->constrained('produccion.libros_campo')->onDelete('cascade');
            $table->dateTime('fecha_registro');

            // Datos cuantitativos (API/Sensores)
            $table->decimal('temperatura', 5, 2); // Celsius
            $table->decimal('humedad', 5, 2);    // Porcentaje
            $table->decimal('precipitacion', 5, 2)->default(0); // mm

            // Datos cualitativos (Observación del técnico)
            $table->string('viento_velocidad')->nullable(); // Ej: "15 km/h" o "Fuerte"
            $table->string('nubosidad')->nullable();       // Ej: "Despejado", "Nublado"
            $table->text('notas_clima')->nullable();

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
        Schema::dropIfExists('registros_climaticos');
    }
};
