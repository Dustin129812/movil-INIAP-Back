<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('AgroDecide.users', function (Blueprint $table) {
            $table->id();
            $table->string('correo_institucional')->unique();
            $table->string('password');
            $table->string('nombre')->nullable();
            $table->string('estado')->default('activo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('AgroDecide.users');
    }
};
