<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_execution_progress', function (Blueprint $table) {
            $table->decimal('accrued_budget', 15, 2)->default(0.00)->after('percentage');
        });
    }

    public function down(): void
    {
        Schema::table('activity_execution_progress', function (Blueprint $table) {
            $table->dropColumn(['accrued_budget', 'evidence_url']);
        });
    }
};
