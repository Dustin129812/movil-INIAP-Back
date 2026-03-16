<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Alterar tabla Provinces
        Schema::table('provinces', function (Blueprint $table) {
            $table->string('codigo_inec', 2)
                ->nullable()
                ->unique()
                ->comment('Código DPA_PROVIN del INEC');
        });

        // 2. Alterar tabla Cantons
        Schema::table('cantons', function (Blueprint $table) {
            $table->string('codigo_inec', 4)
                ->nullable()
                ->unique()
                ->comment('Código DPA_CANTON del INEC');
        });

        // 3. Alterar tabla Parroquias
        Schema::table('parroquias', function (Blueprint $table) {
            $table->string('codigo_inec', 6)
                ->nullable()
                ->unique()
                ->comment('Código DPA_PARROQ del INEC');
        });
    }

    public function down(): void
    {
        Schema::table('parroquias', function (Blueprint $table) {
            $table->dropColumn('codigo_inec');
        });

        Schema::table('cantons', function (Blueprint $table) {
            $table->dropColumn('codigo_inec');
        });

        Schema::table('provinces', function (Blueprint $table) {
            $table->dropColumn('codigo_inec');
        });
    }
};
