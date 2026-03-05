<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transferencia.ensayo_user', function (Blueprint $table) {
            $table->foreignId('ensayo_id')->constrained('transferencia.ensayos')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('public.users')->cascadeOnDelete();
            $table->primary(['ensayo_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ensayo_users');
    }
};
