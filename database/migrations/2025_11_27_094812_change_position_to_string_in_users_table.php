<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // 1. Eliminamos la llave foránea y la columna ID antigua
            // Nota: Asegúrate que el nombre de la FK sea correcto, a veces es users_position_id_foreign
            $table->dropForeign(['position_id']);
            $table->dropColumn('position_id');

            // 2. Creamos la nueva columna de texto
            $table->string('position')->nullable()->after('email');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('position');
            $table->foreignId('position_id')->nullable()->constrained('areas'); // O como se llamara tu tabla
        });
    }
};
