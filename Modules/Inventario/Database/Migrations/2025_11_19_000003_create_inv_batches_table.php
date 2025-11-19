<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inv_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('inv_products'); // FK clave
            $table->string('batch_code');
            $table->date('expiration_date')->nullable();
            $table->decimal('unit_cost', 12, 4);
            $table->decimal('initial_quantity', 12, 2);
            $table->decimal('current_quantity', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_expired')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inv_batches');
    }
};
