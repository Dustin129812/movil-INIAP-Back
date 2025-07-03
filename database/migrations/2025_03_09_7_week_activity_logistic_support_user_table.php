<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
     public function up()
    {
        Schema::create('week_activity_logistic_support_user', function (Blueprint $table) {
            $table->foreignId('weekly_activity_id')->constrained('weekly_activities')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->primary(['weekly_activity_id', 'user_id'], 'week_activity_user_logistic_support_primary');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('week_activity_logistic_support_user');
    }
};
