<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePhotobookTemplatesTable extends Migration
{
    public function up()
    {
        Schema::create('photobook_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('photobook_products')->onDelete('cascade');
            $table->string('name');
            $table->json('layout_data')->nullable();
            $table->string('sample_image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('photobook_templates');
    }
}
