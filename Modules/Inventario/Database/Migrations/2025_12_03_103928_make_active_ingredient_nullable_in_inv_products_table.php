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
        Schema::table('inv_products', function (Blueprint $table) {
            // Redefinimos la columna como nullable
            $table->string('active_ingredient')->nullable()->change();
            $table->dropColumn('scientific_name');
        });
    }

    public function down()
    {
        Schema::table('inv_products', function (Blueprint $table) {
            // En caso de revertir, volvemos a ponerlo como obligatorio (nullable(false))
            $table->string('active_ingredient')->nullable(false)->change();
        });
    }
};
