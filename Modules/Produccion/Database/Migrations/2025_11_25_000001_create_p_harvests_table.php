<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('p_harvests', function (Blueprint $table) {
            $table->id();

            //$table->foreignId('activity_id')->constrained('p_activities')->onDelete('cascade');
            //$table->foreignId('prod_batch_id')->constrained('prod_batches');

            $table->decimal('quantity', 12, 2);
            $table->string('unit')->default('kg');

            $table->string('quality_grade')->default('standard');

            $table->decimal('estimated_market_price', 10, 2)->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::table('p_harvests', function (Blueprint $table) {
        //    $table->dropForeign(['prod_batch_id']);
        });
        Schema::dropIfExists('p_harvests');
    }
};
