<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePhotobookCartsTable extends Migration
{
    public function up()
    {
        Schema::create('photobook_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('photobook_products')->onDelete('cascade');
            $table->foreignId('template_id')->nullable()->constrained('photobook_templates')->onDelete('set null');
            $table->integer('quantity');
            $table->boolean('design_same')->default(false);
            $table->string('session_id')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'session_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('photobook_carts');
    }
}
