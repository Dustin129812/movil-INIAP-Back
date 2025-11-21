<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('p_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('week_activity_id')->nullable()->constrained('weekly_activities');
            $table->index('week_activity_id');
            $table->foreignId('field_id')->nullable()->constrained('p_fields');
            $table->foreignId('prod_batch_id')->nullable()->constrained('prod_batches');

            $table->date('activity_date');
            $table->string('task_type');
            $table->text('observation')->nullable();
            $table->json('extra_data')->nullable();

            // Costos Directos de Mano de Obra
            $table->integer('workers_count')->default(0);
            $table->decimal('labor_hours', 8, 2)->default(0);
            $table->decimal('labor_cost_total', 10, 2)->default(0);

            $table->string('status')->default('completed');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('p_activities');
    }
};
