<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Añadimos la columna permitiendo nulos por seguridad
        Schema::table('trl.tecnologias', function (Blueprint $table) {
            $table->string('programa')->nullable()->after('rubro');
        });
    }

    public function down(): void
    {
        Schema::table('trl.tecnologias', function (Blueprint $table) {
            $table->dropColumn('programa');
        });
    }
};
