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
        // 2025_09_18_2_create_document_workflows_table.php
        Schema::create('document_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('sender_id');
            $table->unsignedBigInteger('recipient_id');
            $table->string('recipient_type')->default('to');
            $table->json('signature_data')->nullable();

            $table->string('action_type')->default('for_information');
            $table->string('status')->default('pending');
            $table->text('comments')->nullable();
            $table->integer('step')->default(1);

            $table->timestamp('read_at')->nullable();
            $table->timestamp('action_at')->nullable();
            $table->timestamps();

            $table->foreign('sender_id')->references('id')->on('users');
            $table->foreign('recipient_id')->references('id')->on('users');
            $table->foreignId('reassigned_to_id')->nullable()->constrained('users');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_workflows');
    }
};
