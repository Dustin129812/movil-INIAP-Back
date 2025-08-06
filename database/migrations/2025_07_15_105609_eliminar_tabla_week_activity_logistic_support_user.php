<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('week_activity_logistic_support_user');
    }

    public function down(): void
    {
        // Si quieres, puedes volver a crear la tabla aquí
    }
};