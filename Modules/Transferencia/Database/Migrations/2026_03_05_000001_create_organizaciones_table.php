<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transferencia.organizaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations');
            $table->string('nombre');
            $table->string('tipo_organizacion');
            $table->integer('participantes_hombres')->default(0);
            $table->integer('participantes_mujeres')->default(0);

            $table->foreignId('user_id')->nullable()->constrained('public.users')->nullOnDelete();
            $table->foreignId('provincia_id')->constrained('provinces');
            $table->foreignId('canton_id')->constrained('cantons');
            $table->foreignId('parroquia_id')->constrained('parroquias');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement('DROP TABLE IF EXISTS transferencia.organizaciones CASCADE;');
    }
};
