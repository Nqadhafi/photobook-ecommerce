<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCouponsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Kode kupon, unik
            $table->string('description')->nullable(); // Deskripsi opsional
            
            // Tipe Diskon (Anda bisa pilih salah satu atau kombinasi)
            // Untuk sekarang, kita gunakan diskon persentase
            $table->decimal('discount_percent', 5, 2)->nullable(); // Misalnya, 10.50 untuk 10,5% // Gunakan decimal untuk presisi
            // $table->decimal('discount_amount', 10, 2)->nullable(); // Diskon nominal tetap, misalnya Rp 50.000 // Dikomentari karena kita pakai persentase dulu
            
            // Batasan Penggunaan
            $table->unsignedInteger('max_uses')->nullable(); // Maksimal total penggunaan (null = tak terbatas)
            $table->unsignedInteger('max_uses_per_user')->default(1); // Maksimal penggunaan per user
            $table->unsignedInteger('times_used')->default(0); // Jumlah kali kupon ini sudah digunakan
            
            // Validitas Waktu
            $table->timestamp('starts_at')->nullable(); // Kapan kupon mulai berlaku
            $table->timestamp('expires_at')->nullable(); // Kapan kupon kadaluarsa

            // Status
            $table->boolean('is_active')->default(true); // Status aktif/nonaktif

            $table->timestamps(); // created_at, updated_at

            // Index untuk kolom yang sering dicari
            $table->index('code');
            $table->index('is_active');
            $table->index('starts_at');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('coupons');
    }
}
