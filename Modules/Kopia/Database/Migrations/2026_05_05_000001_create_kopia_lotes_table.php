<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up() {
        Schema::create('kopia.lotes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid_movil')->unique();
            $table->string('nombre_lote');
            $table->geometry('area', 'polygon', 4326);
            $table->string('ubicacion_manual')->nullable();
            $table->jsonb('condiciones_terreno')->nullable();

            // Relaciones geográficas y administrativas
            $table->unsignedBigInteger('province_id');
            $table->unsignedBigInteger('canton_id');
            $table->string('parroquia')->nullable();
            $table->decimal('altitud', 8, 2)->nullable();
            $table->string('otros_datos_geo')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->timestamps();

            // Llaves foráneas apuntando al esquema donde residen tus tablas core
            $table->foreign('province_id')->references('id')->on('public.provinces')->onDelete('restrict');
            $table->foreign('canton_id')->references('id')->on('public.cantons')->onDelete('restrict');
            $table->foreign('location_id')->references('id')->on('public.locations')->onDelete('restrict');
        });
    }

    public function down() {
        Schema::dropIfExists('kopia.lotes');
    }
};
