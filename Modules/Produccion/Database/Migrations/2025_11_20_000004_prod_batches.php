<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::create('prod_batches', function (Blueprint $table) {
            $table->id();

            // Código único del lote (Ej: NAR-2025-001)
            $table->string('batch_code')->unique();
            $table->enum('environment', ['NURSERY', 'FIELD'])->default('NURSERY');

            // Qué estamos produciendo y con qué receta
            //$table->foreignId('protocol_id')->constrained('prod_protocols');
            //$table->foreignId('field_id')->nullable()->constrained('p_fields');
            // Fechas Reales
            $table->date('start_date'); // Fecha de siembra/inicio
            $table->date('estimated_end_date')->nullable();

            // Cantidades Reales
            $table->integer('initial_quantity'); // Ej: 5000 (Puede ser distinto a la base del protocolo)
            $table->integer('current_quantity'); // Saldo vivo (Disminuye con mortalidad)

            // Estado
            $table->string('current_stage')->nullable(); // "Vivero", "Aclimatación"
            $table->enum('status', ['PLANNING', 'IN_PROGRESS', 'COMPLETED', 'CANCELED'])->default('PLANNING');

            $table->timestamps();
        });
    }

    public function down()
    {
        // En Postgres, usamos SQL directo para forzar el borrado en cascada
        DB::statement('DROP TABLE IF EXISTS prod_batches CASCADE');
    }
};
