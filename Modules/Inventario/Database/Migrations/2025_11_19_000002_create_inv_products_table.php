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
        Schema::create('inv_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('inv_categories');
            $table->string('name');
            $table->string('unit');
            $table->integer('min_stock')->default(5);
            $table->boolean('requires_batch_control')->default(true);
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
        Schema::dropIfExists('inv_products');
    }
};
