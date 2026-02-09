<?php

// create_activity_user_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_protocol', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idi_protocol_id')->constrained('idi_protocols')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users'); // Colaboradores internos
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_protocol');
    }
};
