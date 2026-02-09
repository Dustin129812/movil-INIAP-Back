<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canton_protocol', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idi_protocol_id')->constrained('idi_protocols')->onDelete('cascade');
            $table->foreignId('canton_id')->constrained('cantons');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canton_protocol');
    }
};
