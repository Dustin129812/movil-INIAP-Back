<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trl.tecnologias', function (Blueprint $table) {
            if (!Schema::hasColumn('trl.tecnologias', 'programa')) {
                $table->string('programa', 100)->nullable()->after('region');
            }

            if (!Schema::hasColumn('trl.tecnologias', 'metadata')) {
                $table->jsonb('metadata')->nullable()->after('trl_base');
            }

            $table->index('nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trl.tecnologias', function (Blueprint $table) {
            // Removemos el índice usando el arreglo de la columna
            $table->dropIndex(['nombre']);

            $table->dropColumn('metadata');

            // Opcional: Solo descomentar si deseas remover programa al hacer rollback
            // $table->dropColumn('programa');
        });
    }
};
