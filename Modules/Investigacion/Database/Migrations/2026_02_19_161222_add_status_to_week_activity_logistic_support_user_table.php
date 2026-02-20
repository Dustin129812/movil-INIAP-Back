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
        Schema::table('week_activity_logistic_support_user', function (Blueprint $table) {
            // Añadimos la columna status con 'pending' por defecto
            $table->string('status')->default('pending')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('week_activity_logistic_support_user', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
