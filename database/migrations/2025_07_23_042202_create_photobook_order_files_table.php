<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePhotobookOrderFilesTable extends Migration
{
    public function up()
    {
        Schema::create('photobook_order_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('photobook_orders')->onDelete('cascade');
            $table->foreignId('order_item_id')->nullable()->constrained('photobook_order_items')->onDelete('cascade');
            $table->string('file_path');
            $table->enum('status', ['pending', 'uploaded', 'confirmed', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('photobook_order_files');
    }
}
