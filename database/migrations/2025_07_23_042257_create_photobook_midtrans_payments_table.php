<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePhotobookMidtransPaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('photobook_midtrans_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('photobook_orders')->onDelete('cascade');
            $table->string('snap_token');
            $table->string('redirect_url');
            $table->string('transaction_id')->nullable();
            $table->string('fraud_status')->nullable();
            $table->string('transaction_status')->nullable();
            $table->string('payment_type')->nullable();
            $table->decimal('gross_amount', 12, 2)->nullable();
            $table->string('bank')->nullable();
            $table->json('va_numbers')->nullable();
            $table->string('bill_key')->nullable();
            $table->string('biller_code')->nullable();
            $table->string('payment_code')->nullable();
            $table->string('status_code')->nullable();
            $table->string('status_message')->nullable();
            $table->string('merchant_id')->nullable();
            $table->string('signature_key')->nullable();
            $table->json('payload')->nullable();
            
            // Customer Info untuk Midtrans
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone');
            $table->text('customer_address');
            $table->string('customer_city');
            
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('photobook_midtrans_payments');
    }
}
