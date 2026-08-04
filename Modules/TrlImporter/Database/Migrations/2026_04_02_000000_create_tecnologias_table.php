<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS trl;');

        Schema::create('trl.tecnologias', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('estacion', 50);
            $table->string('region', 50)->nullable();
            $table->string('rubro', 100);
            $table->string('investigador', 150);
            $table->text('nombre');
            $table->string('tipo_tecnologia', 100);
            $table->integer('trl_base')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('trl.tecnologias');
    }
};
