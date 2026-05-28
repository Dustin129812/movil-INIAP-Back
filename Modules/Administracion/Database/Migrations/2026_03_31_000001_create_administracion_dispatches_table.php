<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS administracion');

        Schema::create('administracion.dispatches', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('week_activity_id');
            $table->foreign('week_activity_id')
                ->references('id')
                ->on('public.weekly_activities')
                ->onDelete('cascade');

            $table->unsignedBigInteger('admin_id')->nullable();
            $table->foreign('admin_id')
                ->references('id')
                ->on('public.users');

            $table->enum('status', ['pending', 'processing', 'dispatched', 'rejected'])->default('pending');

            $table->jsonb('requested_items');
            $table->jsonb('dispatched_items')->nullable();

            $table->text('admin_notes')->nullable();

            $table->foreignId('vehicle_id')->nullable()->constrained('administracion.vehicles')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administracion.dispatches');
    }
};
