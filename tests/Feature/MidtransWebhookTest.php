<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\PhotobookProduct;
use App\Models\PhotobookTemplate;
use App\Models\PhotobookOrder;
use App\Models\PhotobookMidtransPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http; // Untuk mocking HTTP call (jika diperlukan nanti)

class MidtransWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected $order;
    protected $paymentRecord;

    protected function setUp(): void
    {
        parent::setUp();

        // Membuat data awal untuk testing
        $user = User::factory()->create();
        $product = PhotobookProduct::factory()->create(['price' => 150000]);
        $template = PhotobookTemplate::factory()->create(['product_id' => $product->id]);

        // Buat order dengan status 'pending'
        $this->order = PhotobookOrder::factory()->create([
            'user_id' => $user->id,
            'order_number' => 'PB-TEST-001',
            'total_amount' => 150000,
            'status' => 'pending',
        ]);

        // Buat record pembayaran Midtrans
        $this->paymentRecord = PhotobookMidtransPayment::factory()->create([
            'order_id' => $this->order->id,
            'snap_token' => 'dummy-snap-token',
            // Field lain bisa diisi sesuai kebutuhan
        ]);
    }

    // Test 1: Webhook menerima notifikasi settlement yang valid dan memperbarui order
    /** @test */
    public function midtrans_webhook_updates_order_status_on_successful_payment()
    {
        // Data notifikasi sukses dari Midtrans (simulasi)
        $notificationData = [
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'order_id' => $this->order->order_number, // Ini kunci untuk mencocokkan order
            'payment_type' => 'bank_transfer',
            'gross_amount' => '150000.00',
            'transaction_id' => 'txn-1234567890',
            'status_code' => '200',
            'status_message' => 'Success, transaction is found',
            'merchant_id' => 'M123456789',
            'signature_key' => 'dummy-signature-key-for-testing', // Signature key asli akan kompleks
            // Tambahkan field lain yang biasa dikirim oleh Midtrans jika diperlukan
        ];

        // Kirim POST request ke endpoint webhook dengan data notifikasi
        $response = $this->postJson(route('midtrans.webhook'), $notificationData);

        // Assert response OK (200)
        $response->assertStatus(200);

        // Refresh model dari database
        $this->order->refresh();
        $this->paymentRecord->refresh();

        // Assert bahwa status order berubah menjadi 'paid'
        $this->assertEquals('paid', $this->order->status);
        $this->assertNotNull($this->order->paid_at);

        // Assert bahwa data pembayaran diupdate
        $this->assertEquals('settlement', $this->paymentRecord->transaction_status);
        $this->assertEquals('accept', $this->paymentRecord->fraud_status);
        $this->assertEquals('txn-1234567890', $this->paymentRecord->transaction_id);
        $this->assertNotNull($this->paymentRecord->paid_at);
        // Assert field lain jika diperlukan
    }

    // Test 2: Webhook menangani notifikasi untuk order yang tidak ditemukan
    /** @test */
    public function midtrans_webhook_handles_notification_for_nonexistent_order()
    {
        $notificationData = [
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'order_id' => 'NON-EXISTENT-ORDER-NUMBER', // Order number yang tidak ada
            'payment_type' => 'bank_transfer',
            'gross_amount' => '100000.00',
            'transaction_id' => 'txn-0987654321',
            'status_code' => '200',
            'status_message' => 'Success, transaction is found',
            'merchant_id' => 'M123456789',
            'signature_key' => 'dummy-signature-key-2',
        ];

        // Mock logging untuk memastikan pesan warning dicatat
        // \Log::shouldReceive('warning')->once(); // Jika menggunakan mocking

        // Kirim POST request
        $response = $this->postJson(route('midtrans.webhook'), $notificationData);

        // Assert response OK (200) - Midtrans tetap butuh 200 meski data tidak ditemukan
        $response->assertStatus(200);

        // Assert bahwa order asli tidak berubah
        $this->order->refresh();
        $this->assertEquals('pending', $this->order->status);
        $this->assertNull($this->order->paid_at);
    }

     // Test 3: Webhook menangani notifikasi pembayaran yang ditolak/fraud
    /** @test */
    public function midtrans_webhook_does_not_update_order_status_on_failed_payment()
    {
        $notificationData = [
            'transaction_status' => 'settlement', // Status settlement
            'fraud_status' => 'deny', // Tapi fraud status deny
            'order_id' => $this->order->order_number,
            'payment_type' => 'credit_card',
            'gross_amount' => '150000.00',
            'transaction_id' => 'txn-fraud-123',
            'status_code' => '202', // Status code untuk fraud
            'status_message' => 'Payment was denied by Fraud Detection System',
            'merchant_id' => 'M123456789',
            'signature_key' => 'dummy-signature-key-3',
        ];

        $response = $this->postJson(route('midtrans.webhook'), $notificationData);

        $response->assertStatus(200);

        $this->order->refresh();
        $this->paymentRecord->refresh();

        // Assert bahwa status order TIDAK berubah menjadi 'paid'
        $this->assertEquals('pending', $this->order->status); // Masih pending
        $this->assertNull($this->order->paid_at);

        // Tapi data pembayaran tetap diupdate
        $this->assertEquals('settlement', $this->paymentRecord->transaction_status);
        $this->assertEquals('deny', $this->paymentRecord->fraud_status);
        $this->assertEquals('txn-fraud-123', $this->paymentRecord->transaction_id);
        $this->assertNull($this->paymentRecord->paid_at); // paid_at tetap null
    }

    // Test 4: Webhook menangani notifikasi expired
    /** @test */
    public function midtrans_webhook_handles_expired_transaction()
    {
        $notificationData = [
            'transaction_status' => 'expire', // Transaksi expired
            'fraud_status' => 'accept',
            'order_id' => $this->order->order_number,
            'payment_type' => 'echannel', // Misalnya, e-channel untuk Mandiri Bill
            'gross_amount' => '150000.00',
            'status_code' => '407', // Status code untuk expired
            'status_message' => 'Transaction is expired',
            'merchant_id' => 'M123456789',
            'signature_key' => 'dummy-signature-key-4',
            // echannel specific
            'bill_key' => 'BILL-KEY-123',
            'biller_code' => 'BILLER-CODE-456',
        ];

        $response = $this->postJson(route('midtrans.webhook'), $notificationData);

        $response->assertStatus(200);

        $this->order->refresh();
        $this->paymentRecord->refresh();

        // Assert bahwa status order TIDAK berubah menjadi 'paid'
        // (Kamu bisa memutuskan apakah mau ubah status order jadi 'cancelled' atau tidak)
        // Untuk sekarang, kita asumsi tidak mengubah status order
        $this->assertEquals('pending', $this->order->status);
        $this->assertNull($this->order->paid_at);

        // Tapi data pembayaran diupdate
        $this->assertEquals('expire', $this->paymentRecord->transaction_status);
        $this->assertEquals('accept', $this->paymentRecord->fraud_status);
        $this->assertEquals('BILL-KEY-123', $this->paymentRecord->bill_key);
        $this->assertEquals('BILLER-CODE-456', $this->paymentRecord->biller_code);
    }
}
