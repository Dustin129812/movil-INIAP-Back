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
        Schema::create('multidisciplinary_groups', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();

            $table->string('name');

            $table->foreignId('location_id')->constrained('locations');
            $table->foreignId('rubro_id')->constrained('rubros');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('multidisciplinary_groups');
    }
};
