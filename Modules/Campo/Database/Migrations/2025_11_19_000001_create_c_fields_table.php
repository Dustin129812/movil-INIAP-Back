<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('p_fields', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['OPEN_FIELD', 'FACILITY'])->default('OPEN_FIELD');
            $table->decimal('area_hectares', 8, 2);
            $table->string('current_crop')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS p_fields CASCADE');
    }
};
