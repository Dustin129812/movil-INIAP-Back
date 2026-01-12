<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Pivote para INDICADORES
        Schema::create('novelty_activity_performance_indicator', function (Blueprint $table) {
            $table->id();
            $table->foreignId('novelty_activity_id')->constrained('novelty_activities')->onDelete('cascade');
            // Ojo aquí: asegúrate que la tabla se llama 'performance_indicators' en tu BD
            $table->foreignId('performance_indicator_id')->constrained('performance_indicators')->onDelete('cascade');
            $table->timestamps();
        });

        // 2. Pivote para MATERIALES (la que te dio error antes)
        // Si ya la creaste en el paso anterior, comenta este bloque.
        if (!Schema::hasTable('novelty_activity_material')) {
            Schema::create('novelty_activity_material', function (Blueprint $table) {
                $table->id();
                $table->foreignId('novelty_activity_id')->constrained('novelty_activities')->onDelete('cascade');
                $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
                $table->decimal('quantity', 8, 2)->nullable();
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        // 3. Pivote para APOYO LOGÍSTICO
        Schema::create('novelty_activity_logistic_support', function (Blueprint $table) {
            $table->id();
            $table->foreignId('novelty_activity_id')->constrained('novelty_activities')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('novelty_activity_performance_indicator');
        Schema::dropIfExists('novelty_activity_material');
        Schema::dropIfExists('novelty_activity_logistic_support');
    }
};
