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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();

            $table->text('name');
            $table->decimal('budget', 15, 2)->default(0);
            $table->decimal('ponderacion', 5, 2)->default(0);
            $table->string('funding_source_name')->nullable();

            $table->foreignId('rubro_id')->constrained('rubros');
            $table->foreignId('location_id')->constrained('locations');
            $table->foreignId('budget_types_id')->nullable()->constrained('budget_types');
            $table->foreignId('crop_id')->nullable()->constrained('crops');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
