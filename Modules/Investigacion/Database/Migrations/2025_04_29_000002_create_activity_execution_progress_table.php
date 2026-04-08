<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_execution_progress', function (Blueprint $table) {
            $table->id();
            $table->date('month');
            $table->decimal('percentage', 5, 2)->default(0.00);
            $table->decimal('accrued_budget', 15, 2)->default(0.00);
            $table->string('observation')->nullable();
            $table->string('evidence_url')->nullable();
            $table->timestamps();

            $table->softDeletes();
            $table->foreignId('activity_id')->constrained()->onDelete('cascade');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_execution_progress');
    }
};
