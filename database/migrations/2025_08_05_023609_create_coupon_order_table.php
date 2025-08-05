<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Coupon;
use App\Models\PhotobookOrder;

class CreateCouponOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coupon_order', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Coupon::class)->constrained()->onDelete('cascade');
            $table->foreignIdFor(PhotobookOrder::class, 'order_id')->constrained('photobook_orders')->onDelete('cascade');
            // Bisa menyimpan nilai diskon yang diterapkan saat itu, jika harga/order bisa berubah
            // $table->decimal('discount_amount_applied', 10, 2)->nullable(); 
            $table->timestamps(); // created_at bisa mencatat kapan kupon digunakan

            // Mencegah penggunaan kupon yang sama untuk order yang sama lebih dari sekali
            $table->unique(['coupon_id', 'order_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('coupon_order');
    }
}
