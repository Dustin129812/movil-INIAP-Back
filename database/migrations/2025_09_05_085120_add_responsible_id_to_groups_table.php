<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->foreignId('responsible_id')->nullable()->after('creator_id')->constrained('users')->nullOnDelete();
        });

        DB::table('groups')->whereNotNull('creator_id')->update([
            'responsible_id' => DB::raw('creator_id')
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropForeign(['responsible_id']);
            $table->dropColumn('responsible_id');
        });
    }
};
