<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePhotobookOrderItemsTable extends Migration
{
    public function up()
    {
        Schema::create('photobook_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('photobook_orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('photobook_products')->onDelete('cascade');
            $table->foreignId('template_id')->nullable()->constrained('photobook_templates')->onDelete('set null');
            $table->integer('quantity');
            $table->decimal('price', 10, 2);
            $table->boolean('design_same')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('photobook_order_items');
    }
}
