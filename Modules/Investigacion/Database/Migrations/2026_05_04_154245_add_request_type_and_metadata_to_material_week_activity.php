<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_week_activity', function (Blueprint $table) {
            $table->string('request_type')->nullable()->after('description');
            $table->jsonb('metadata')->nullable()->after('request_type');
        });
    }

    public function down(): void
    {
        Schema::table('material_week_activity', function (Blueprint $table) {
            $table->dropColumn(['request_type', 'metadata']);
        });
    }
};
