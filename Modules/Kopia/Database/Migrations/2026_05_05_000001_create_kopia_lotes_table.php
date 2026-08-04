<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up() {
        Schema::create('kopia.lotes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid_movil')->unique();
            $table->uuid('dispositivo_invitado_id')->nullable();
            $table->string('estado_verificacion')->default('pendiente');
            $table->string('nombre_lote');
            $table->geometry('area', 'polygon', 4326);
            $table->string('ubicacion_manual')->nullable();
            $table->jsonb('condiciones_terreno')->nullable();
            $table->string('tipo_riego', 30)->default('gravedad')->comment('Admite: gravedad, goteo, aspersión, microaspersión');

            $table->unsignedBigInteger('province_id');
            $table->unsignedBigInteger('canton_id');
            $table->string('parroquia')->nullable();
            $table->decimal('altitud', 8, 2)->nullable();
            $table->string('otros_datos_geo')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->timestamps();

            $table->foreign('province_id')
                ->references('id')->
                on('public.provinces')->
                onDelete('restrict');

            $table->foreign('canton_id')
                ->references('id')
                ->on('public.cantons')
                ->onDelete('restrict');

            $table->foreign('location_id')
                ->references('id')
                ->on('public.locations')
                ->onDelete('restrict');

            $table->foreign('dispositivo_invitado_id')
                ->references('id')
                ->on('kopia.dispositivos_invitados')
                ->onDelete('set null');
        });
    }

    public function down() {
        Schema::dropIfExists('kopia.lotes');
    }
};
