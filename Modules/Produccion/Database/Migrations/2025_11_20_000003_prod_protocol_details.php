<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::create('prod_protocol_details', function (Blueprint $table) {
            $table->id();
            //$table->foreignId('protocol_id')->constrained('prod_protocols')->onDelete('cascade');

            $table->integer('day_start');
            $table->integer('day_end')->nullable();

            $table->string('stage');
            $table->string('task');
            $table->enum('resource_type', ['PRODUCT', 'MACHINERY', 'LABOR', 'SERVICE']);

            //$table->foreignId('inv_product_id')->nullable()->constrained('inv_products');
            //$table->foreignId('inv_machinery_id')->nullable()->constrained('inv_machinery');

            $table->string('resource_name')->nullable();

            $table->decimal('quantity', 12, 4);

            $table->decimal('reference_unit_cost', 12, 4);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::table('prod_protocol_details', function (Blueprint $table) {
        //    $table->dropForeign(['inv_product_id']);
        //    $table->dropForeign(['inv_machinery_id']);
        });
        Schema::dropIfExists('prod_protocol_details');
    }
};
