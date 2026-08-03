<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investigaciones.canton_protocol', function (Blueprint $table) {
            $table->id();

            $table->foreignId('idi_protocol_id')
                ->constrained('investigaciones.idi_protocols')
                ->cascadeOnDelete();

            $table->foreignId('canton_id')
                ->constrained('cantons')
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investigaciones.canton_protocol');
    }
};
