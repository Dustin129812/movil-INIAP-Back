<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investigaciones.protocol_annexes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('protocol_id')
                ->constrained('investigaciones.idi_protocols')
                ->cascadeOnDelete();

            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type', 100);
            $table->unsignedBigInteger('file_size');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investigaciones.protocol_annexes');
    }
};
