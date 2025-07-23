<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePhotobookOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('photobook_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('order_number')->unique();
            $table->decimal('total_amount', 10, 2);
            
            // Status Order
            $table->enum('status', [
                'pending', 
                'paid', 
                'file_upload', 
                'processing', 
                'ready', 
                'completed', 
                'cancelled'
            ])->default('pending');
            
            // Customer Info
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone');
            $table->text('customer_address');
            $table->string('customer_city');
            $table->string('customer_postal_code');
            
            // Pickup Info
            $table->string('pickup_code')->nullable();
            
            // Timestamps
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('file_uploaded_at')->nullable();
            $table->timestamp('processing_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('photobook_orders');
    }
}
