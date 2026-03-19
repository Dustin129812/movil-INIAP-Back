<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        Schema::table('provinces', function (Blueprint $table) {
            $table->string('codigo_inec', 2)
                ->nullable()
                ->unique()
                ->comment('Código DPA_PROVIN del INEC');
        });


        Schema::table('cantons', function (Blueprint $table) {
            $table->string('codigo_inec', 4)
                ->nullable()
                ->unique()
                ->comment('Código DPA_CANTON del INEC');
        });
        */
        Schema::table('parroquias', function (Blueprint $table) {
            $table->string('codigo_inec', 4)
                ->nullable()
                ->unique()
                ->comment('Código DPA_PARR del INEC');
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
