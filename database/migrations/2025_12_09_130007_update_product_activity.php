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
    Schema::table('products', function (Blueprint $table){
        // AGREGAR ->nullable() ES LA SOLUCIÓN
        $table->foreignId('budget_types_id')
              ->nullable()                   
              ->constrained('budget_types'); 
    });

    Schema::table('activities', function (Blueprint $table){
        // Asegúrate de que esto esté bien escrito también (sin _id)
        $table->float('accrued_budget')->default(0);
        
    });

    Schema::table('activity_execution_progress', function (Blueprint $table){
        $table->string('observation')->nullable();
        
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
