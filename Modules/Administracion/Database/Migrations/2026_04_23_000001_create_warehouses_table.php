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
        Schema::create('administracion.warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ej: Bodega Central Santa Catalina
            $table->foreignId('location_id')->constrained('public.locations'); // Vinculado a la Estación
            $table->foreignId('responsible_id')->constrained('public.users'); // Rafael o Wilmer
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
        Schema::dropIfExists('administracion.warehouses');
    }
};
