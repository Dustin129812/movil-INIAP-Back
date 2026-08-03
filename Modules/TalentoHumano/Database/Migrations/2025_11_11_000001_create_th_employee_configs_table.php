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
        Schema::create('th_employee_configs', function (Blueprint $table) {

            $table->foreignId('user_id')
                ->primary()
                ->constrained('users')
                ->onDelete('cascade');

            $table->decimal('rmu', 8, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('th_employee_configs');
    }
};
