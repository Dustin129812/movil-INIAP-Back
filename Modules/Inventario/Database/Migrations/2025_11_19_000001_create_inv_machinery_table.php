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
        Schema::create('inv_machinery', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->index(); // 'TOOL' o 'VEHICLE'

            // === DATOS FINANCIEROS COMUNES ===
            $table->decimal('acquisition_cost', 12, 2);
            $table->integer('acquisition_year');
            $table->integer('useful_life_years');

            // === USO (La clave de tu fórmula) ===
            // Para VEHICLE: Lo ingresa el usuario (ej: 1000 horas/año)
            // Para TOOL: Se calcula (ej: 8h * 20d * 12m = 1920)
            $table->decimal('annual_usage_hours', 10, 2)->nullable();

            // === EL CEREBRO DE LA DIFERENCIA ===
            // TOOL guarda: {"hours_per_day": 8, "days_per_month": 20}
            // VEHICLE guarda: {"fuel": 2.8, "tires": 350, "oil": 35...}
            $table->json('cost_parameters')->nullable();

            // === RESULTADO ===
            // Aquí se guarda tu 0.0197 o tu 15.25
            $table->decimal('calculated_hourly_cost', 12, 4)->default(0);

            $table->boolean('is_active')->default(true);
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
        Schema::dropIfExists('inv_machinery');
    }
};
