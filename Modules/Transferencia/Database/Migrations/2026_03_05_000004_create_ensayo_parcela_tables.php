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
        Schema::create('transferencia.ensayo_parcela', function (Blueprint $table) {
            $table->foreignId('ensayo_id')->constrained('transferencia.ensayos')->cascadeOnDelete();
            $table->foreignId('parcela_id')->constrained('produccion.lotes')->cascadeOnDelete();
            $table->primary(['ensayo_id', 'parcela_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ensayo_parcela');
    }
};
