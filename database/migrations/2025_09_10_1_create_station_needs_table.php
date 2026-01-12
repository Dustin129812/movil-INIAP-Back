<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('station_needs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('location_id')->constrained('locations');
            $table->date('fill_date');

            $table->foreignId('expense_type_id')->nullable();
            $table->text('description');
            $table->decimal('estimated_amount', 12, 2);
            $table->string('priority');
            $table->text('expected_impact');
            $table->string('impact_type');
            $table->text('problem_to_solve');
            $table->text('investment_risk');
            $table->integer('administrative_time_months')->nullable();
            $table->integer('execution_time_months')->nullable();
            $table->boolean('has_supporting_documents')->default(false);
            $table->boolean('requires_technical_studies')->default(false);
            $table->boolean('has_technical_studies')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('station_needs');
    }
};
