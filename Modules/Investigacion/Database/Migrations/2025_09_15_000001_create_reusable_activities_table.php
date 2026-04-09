<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reusable_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('activity_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('activity_type')->default('tecnica');
            $table->text('description');
            $table->string('work_location')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
        });

        Schema::create('reusable_activity_material', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reusable_activity_id')->constrained()->onDelete('cascade');
            $table->foreignId('material_id')->constrained()->onDelete('cascade');
            $table->integer('quantity')->nullable();
            $table->string('description')->nullable();
        });

        // Tabla pivote para indicadores
        Schema::create('reusable_activity_performance_indicator', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reusable_activity_id')->constrained('reusable_activities')->onDelete('cascade');
            $table->foreignId('performance_indicator_id')->constrained('performance_indicators')->onDelete('cascade');
        });

        // Tabla pivote para apoyo logístico
        Schema::create('reusable_activity_logistic_support', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reusable_activity_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // El usuario de apoyo
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reusable_activity_logistic_support');
        Schema::dropIfExists('reusable_activity_performance_indicator');
        Schema::dropIfExists('reusable_activity_material');
        Schema::dropIfExists('reusable_activities');
    }
};
