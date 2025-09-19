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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('internal_id')->unique()->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('subject');
            $table->json('content')->nullable();
            $table->string('status')->default('borrador');
            $table->string('category')->nullable();
            $table->string('typification')->nullable();
            $table->string('reference_number')->nullable();

            $table->integer('version')->default(1);

            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('documents')->onDelete('cascade');
            $table->foreignId('document_type_id')->nullable();
            $table->foreignId('on_behalf_of_user_id')->nullable()->constrained('users');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
