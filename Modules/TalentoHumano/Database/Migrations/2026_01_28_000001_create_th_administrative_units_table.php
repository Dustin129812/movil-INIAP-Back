<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::create('th_administrative_units', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., 'Human Resources Directorate'
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('th_administrative_units');
    }
};
