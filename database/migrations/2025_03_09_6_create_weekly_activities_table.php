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
        Schema::create('weekly_activities', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();

            $table->text('description');
            $table->date('date');
            $table->integer('percentage');
            $table->string('work_location');
            $table->enum('status', ['pending', 'approved', 'rejected', 'in progress', 'completed'])->default('pending');
            $table->text('observations')->nullable()->after('percentage');

            $table->foreignId('activity_id')->constrained('activities');
            $table->foreignId('user_id')->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_activities');
    }
};
