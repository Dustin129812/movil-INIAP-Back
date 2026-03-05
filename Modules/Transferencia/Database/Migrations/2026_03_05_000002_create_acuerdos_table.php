<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transferencia.acuerdos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizacion_id')->constrained('transferencia.organizaciones')->cascadeOnDelete();

            $table->date('fecha_firma');
            $table->integer('anios_vigencia');
            $table->string('archivo_acuerdo_path')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement('DROP TABLE IF EXISTS transferencia.acuerdos CASCADE;');
    }
};
