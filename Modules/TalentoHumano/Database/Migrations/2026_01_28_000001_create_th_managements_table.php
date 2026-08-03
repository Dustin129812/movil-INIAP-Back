<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::create('th_managements', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., 'Payroll Management'
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('th_managements');
    }
};
