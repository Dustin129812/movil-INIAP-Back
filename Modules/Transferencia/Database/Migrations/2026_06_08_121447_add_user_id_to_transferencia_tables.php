<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'transferencia.organizaciones',
            'transferencia.acuerdos',
            'transferencia.ensayos',
            'transferencia.parcelas',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->constrained('public.users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'transferencia.parcelas',
            'transferencia.ensayos',
            'transferencia.acuerdos',
            'transferencia.organizaciones',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            });
        }
    }
};
