<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('th_process_id')->nullable()->constrained('th_processes');
            $table->foreignId('th_administrative_unit_id')->nullable()->constrained('th_administrative_units');
            $table->foreignId('th_management_id')->nullable()->constrained('th_managements');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['th_process_id']);
            $table->dropForeign(['th_administrative_unit_id']);
            $table->dropForeign(['th_management_id']);

            $table->dropColumn(['th_process_id', 'th_administrative_unit_id', 'th_management_id']);
        });
    }
};
