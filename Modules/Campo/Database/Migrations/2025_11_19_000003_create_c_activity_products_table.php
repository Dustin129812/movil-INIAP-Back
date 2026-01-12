<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('p_activity_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('p_activities')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('inv_products');

            $table->decimal('quantity', 10, 2);

            $table->decimal('historical_unit_cost', 10, 4);
            $table->decimal('total_cost', 10, 2);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('p_activity_products');
    }
};
