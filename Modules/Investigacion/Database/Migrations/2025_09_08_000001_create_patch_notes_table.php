<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patch_notes', function (Blueprint $table) {
            $table->id();
            $table->string('version');
            $table->string('title');
            $table->longText('content');
            $table->date('release_date');
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patch_notes');
    }
};
