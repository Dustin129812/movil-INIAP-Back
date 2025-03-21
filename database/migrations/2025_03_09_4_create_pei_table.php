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
        Schema::create('pei', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();

            $table->string('name');

            $table->foreignId('locations_id')->constrained('locations');
            $table->foreignId('rubro_id')->constrained('rubros');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('investigation_area_id')->constrained('investigation_areas');
            $table->foreignId('investigation_line_id')->constrained('investigation_lines');
            $table->foreignId('objetive_id')->constrained('objetives');
            $table->foreignId('performance_indicator_id')->constrained('performance_indicators');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pei');
    }
};
