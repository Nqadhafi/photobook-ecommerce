<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePhotobookNotificationsTable extends Migration
{
    public function up()
    {
        Schema::create('photobook_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('photobook_orders')->onDelete('cascade');
            $table->string('title');
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->string('action_url')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('is_read');
        });
    }

    public function down()
    {
        Schema::dropIfExists('photobook_notifications');
    }
}
