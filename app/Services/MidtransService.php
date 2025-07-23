<?php

namespace App\Services;

use App\Models\PhotobookOrder;
use App\Models\PhotobookMidtransPayment;
use App\Services\NotificationService;
use Midtrans\Config;
use Midtrans\Snap;
use Illuminate\Support\Facades\Log;

class MidtransService
{
    protected $notificationService;
    public function __construct(NotificationService $notificationService)
    {
        // Set konfigurasi Midtrans
        Config::$serverKey = config('midtrans.server_key');
        Config::$clientKey = config('midtrans.client_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = true; // Hapus karakter sensitif
        Config::$is3ds = true; // Aktifkan 3D Secure
        $this->notificationService = $notificationService;
    }

    /**
     * Membuat transaksi di Midtrans dan mendapatkan redirect_url
     *
     * @param PhotobookOrder $order
     * @return array
     */
    public function createTransaction(PhotobookOrder $order): array
    {
        $params = [
            'transaction_details' => [
                'order_id' => $order->order_number, // Gunakan order_number sebagai ID transaksi Midtrans
                'gross_amount' => (int) $order->total_amount,
            ],
            'customer_details' => [
                'first_name' => $order->customer_name,
                'email' => $order->customer_email,
                'phone' => $order->customer_phone,
                'billing_address' => [
                    'first_name' => $order->customer_name,
                    'address' => $order->customer_address,
                    'city' => $order->customer_city,
                    'postal_code' => $order->customer_postal_code,
                    'country_code' => $order->country ?? 'IDN', // Pastikan ada field country atau default ke 'ID'
                ],
            ],
            // 'item_details' bisa ditambahkan jika diperlukan untuk detail di Midtrans dashboard
        ];

        try {
            // Dapatkan Snap Token
            $snapToken = Snap::getSnapToken($params);

            // Dapatkan Redirect URL (opsional, biasanya dibentuk dari snap token di frontend)
            // Tapi untuk kemudahan, kita bisa simpan snap token dan buat URL di frontend
            // Atau kita bisa kembalikan snap token saja dan buat URL di frontend.
            // Untuk sekarang, kita kembalikan snap token.
            // URL umumnya: https://app.sandbox.midtrans.com/snap/v1/vtweb/$snapToken (untuk vtweb)
            // Atau gunakan Snap.js di frontend dengan snap token.

            return [
                'status' => 'success',
                'snap_token' => $snapToken,
                // 'redirect_url' => "https://app.sandbox.midtrans.com/snap/v1/vtweb/$snapToken" // Jika pakai vtweb
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Menangani notifikasi dari Midtrans
     * Ini akan dipanggil oleh route webhook
     *
     * @param array $notification
     * @return void
     */
    public function handleNotification(array $notification): void
    {
        $transactionStatus = $notification['transaction_status']; // capture, settlement, deny, cancel, expire
        $fraudStatus = $notification['fraud_status'] ?? null; // accept, deny, challenge
        $orderId = $notification['order_id'];
        $paymentType = $notification['payment_type'];
        $grossAmount = $notification['gross_amount'];

        // Cari order berdasarkan order_number
        $order = PhotobookOrder::where('order_number', $orderId)->first();

        if (!$order) {
            // Log error: Order tidak ditemukan
            Log::warning("Midtrans Notification: Order with number {$orderId} not found.");
            return;
        }

        // Cari atau buat record pembayaran
        $paymentRecord = PhotobookMidtransPayment::firstOrNew(['order_id' => $order->id]);
        $paymentRecord->fill([
            'transaction_id' => $notification['transaction_id'] ?? null,
            'fraud_status' => $fraudStatus,
            'transaction_status' => $transactionStatus,
            'payment_type' => $paymentType,
            'gross_amount' => $grossAmount,
            'bank' => $notification['bank'] ?? null,
            'va_numbers' => isset($notification['va_numbers']) ? json_encode($notification['va_numbers']) : null,
            'bill_key' => $notification['bill_key'] ?? null,
            'biller_code' => $notification['biller_code'] ?? null,
            'payment_code' => $notification['payment_code'] ?? null,
            'status_code' => $notification['status_code'] ?? null,
            'status_message' => $notification['status_message'] ?? null,
            'merchant_id' => $notification['merchant_id'] ?? null,
            'signature_key' => $notification['signature_key'] ?? null,
            'payload' => json_encode($notification), // Simpan raw data untuk debugging
            'customer_name' => $order->customer_name,
            'customer_email' => $order->customer_email,
            'customer_phone' => $order->customer_phone,
            'customer_address' => $order->customer_address,
            'customer_city' => $order->customer_city,
        ]);

        // Update timestamp paid_at jika pembayaran berhasil
        $paymentRecord->paid_at = null;

        if ($transactionStatus === 'settlement' && $fraudStatus === 'accept') {
            $paymentRecord->paid_at = now();
        }
        $paymentRecord->save();

        // Update status order berdasarkan status pembayaran
        if ($transactionStatus == 'settlement' && $fraudStatus == 'accept') {
            if ($order->status === 'pending') { // Hanya update jika status masih pending
                $order->update([
                    'status' => 'paid',
                    'paid_at' => now()
                ]);
                // TODO: Kirim notifikasi email/customer bahwa pembayaran berhasil
                $this->notificationService->notifyCustomer(
                    $order->user,
                    'Payment Successful',
                    "Your payment for order #{$order->order_number} has been successfully processed. You can now upload your design files.",
                    $order,
                    url("/orders/{$order->id}/upload") // Contoh action URL
                );

                // Notifikasi ke admin
                $this->notificationService->notifyAdmin(
                    'New Paid Order',
                    "Order #{$order->order_number} has been paid and is awaiting file uploads.",
                    $order
                );
            }
        }

        // Tambahkan logika untuk status lain jika diperlukan (expire, cancel, dll)
        // Misalnya, jika expire, ubah status order ke cancelled.
    }
}
