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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->text('content')->nullable();
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->string('guest_id')->nullable();
            $table->timestamps();
            $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');

            $table->string('message_type')->default('text');
            $table->string('file_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
