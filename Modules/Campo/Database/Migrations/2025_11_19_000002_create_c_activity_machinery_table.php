<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('p_activity_machinery', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('p_activities')->onDelete('cascade');
            $table->foreignId('machinery_id')->constrained('inv_machinery');

            // Aquí usamos tu lógica corregida: Puede ser horas o km
            $table->decimal('hours_or_km', 10, 2);

            // Foto del costo en ese momento ($0.40/km o $10/hora)
            $table->decimal('historical_hourly_cost', 10, 4);
            $table->decimal('total_cost', 10, 2);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('p_activity_machinery');
    }
};
