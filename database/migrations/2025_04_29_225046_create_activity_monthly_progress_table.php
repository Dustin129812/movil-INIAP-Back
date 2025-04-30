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
        Schema::create('activity_monthly_progress', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->date('month');
            $table->decimal('percentage', 5, 2);

            $table->foreignId('activity_id')->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_monthly_progress');
    }
};
