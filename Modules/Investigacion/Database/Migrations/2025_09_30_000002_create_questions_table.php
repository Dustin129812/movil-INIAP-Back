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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->onDelete('cascade');
            $table->string('text');
            $table->enum('type', [
                'text',       // respuesta abierta
                'textarea',   // respuesta larga
                'radio',      // opción única
                'checkbox',   // opción múltiple
                'scale',      // escala 1-5 o 1-10
                'boolean'     // sí/no
            ])->default('text');
            $table->boolean('is_required')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
