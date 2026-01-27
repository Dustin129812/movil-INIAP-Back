<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // 'pending': Esperando revisión
            // 'approved': Aprobado por el director
            // 'rejected': Rechazado (requiere corrección)
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('funding_source_name');

            // Campo para explicar por qué se rechazó (opcional)
            $table->text('admin_observation')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['status', 'admin_observation']);
        });
    }
};
