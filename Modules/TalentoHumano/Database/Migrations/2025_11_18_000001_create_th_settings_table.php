<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('th_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // Ej: 'daf_authority_id'
            $table->text('value')->nullable(); // El ID del usuario (guardado como texto o entero)
            $table->string('description')->nullable(); // Para saber qué es esta clave
            $table->timestamps();
        });

        DB::table('th_settings')->insert([
            [
                'key' => 'daf_authority_id',
                'value' => null,
                'description' => 'Usuario que firma como Director Administrativo Financiero (DAF)',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'mobility_authority_id',
                'value' => null,
                'description' => 'Usuario que firma como Responsable de Movilidad',
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('th_settings');
    }
};
