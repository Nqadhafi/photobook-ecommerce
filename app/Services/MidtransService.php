<?php

namespace App\Services;

use App\Models\PhotobookOrder;
use App\Models\PhotobookMidtransPayment;
use App\Services\NotificationService;
use App\Services\GoogleDriveService;
use Midtrans\Config;
use Midtrans\Snap;
use Illuminate\Support\Facades\Log;

class MidtransService
{
    protected $notificationService;
    protected $googleDriveService;
    public function __construct(NotificationService $notificationService, GoogleDriveService $googleDriveService)
    {
        // Set konfigurasi Midtrans
        Config::$serverKey = config('midtrans.server_key');
        Config::$clientKey = config('midtrans.client_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = true; // Hapus karakter sensitif
        Config::$is3ds = true; // Aktifkan 3D Secure
        $this->notificationService = $notificationService;
        $this->googleDriveService = $googleDriveService;
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
            // --- Simpan record pembayaran seperti sebelumnya ---
            $paymentRecord = PhotobookMidtransPayment::firstOrNew(['order_id' => $order->id]);
            $paymentRecord->fill([
                // ... field-field lain seperti sebelumnya ...
                'transaction_id' => $notification['transaction_id'] ?? null,
                'fraud_status' => $fraudStatus,
                'transaction_status' => $transactionStatus,
                'payment_type' => $paymentType,
                'gross_amount' => $grossAmount,
                // ... field-field lain ...
                'paid_at' => now(), // Set paid_at sebelumnya untuk konsistensi
            ]);
            $paymentRecord->save();
            // --- Akhir simpan pembayaran ---

            try {
                // --- Coba buat folder Google Drive ---
                Log::info("Attempting to create Google Drive folder for Order ID: {$order->id}");
                $folderUrl = $this->googleDriveService->createOrderFolder($order);

                // Jika berhasil membuat folder:
                $order->update([
                    'status' => 'file_upload', // Status baru
                    'paid_at' => now(), // Pastikan paid_at juga diisi
                    'google_drive_folder_url' => $folderUrl // Simpan URL folder
                ]);

                Log::info("Order ID {$order->id} status updated to 'file_upload' and folder URL saved.");

                // --- Kirim notifikasi sukses (termasuk link drive) ---
                $this->notificationService->notifyCustomer(
                    $order->user,
                    'Payment Successful & Folder Ready',
                    "Your payment for order #{$order->order_number} has been successfully processed. Your Google Drive folder is ready for file uploads: {$folderUrl}",
                    $order
                    // Anda bisa menambahkan action URL jika diperlukan, atau gunakan $folderUrl langsung di frontend
                );

                // Notifikasi ke admin
                $this->notificationService->notifyAdmin(
                    'New Paid Order with Drive Folder',
                    "Order #{$order->order_number} has been paid and a Google Drive folder has been created. Folder URL: {$folderUrl}",
                    $order
                );

                // --- Kirim pesan WhatsApp ---
                // Anda perlu memastikan $order->customer_phone ada dan valid
                if (!empty($order->customer_phone)) {
                    $whatsappMessage = "Halo {$order->customer_name},\n\nPembayaran untuk order #{$order->order_number} telah berhasil.\n\nSilakan upload file desain Anda ke folder Google Drive ini: {$folderUrl}\n\nTerima kasih!";
                    $this->notificationService->sendWhatsAppMessage($order->customer_phone, $whatsappMessage);
                } else {
                     Log::warning("Customer phone number is missing for Order ID: {$order->id}. Skipping WhatsApp notification.");
                }

            } catch (\Exception $e) {
                // --- Tangani Kegagalan Pembuatan Folder ---
                Log::error('Failed to create Google Drive folder or send notifications after payment.', [
                    'order_id' => $order->id,
                    'exception_message' => $e->getMessage(),
                    'exception_trace' => $e->getTraceAsString()
                ]);

                // Tetap update status order ke 'paid' karena pembayaran berhasil
                // Tidak isi google_drive_folder_url
                $order->update([
                    'status' => 'paid', // Status dasar untuk pembayaran sukses
                    'paid_at' => now()
                ]);

                // --- Kirim notifikasi error ke customer ---
                $this->notificationService->notifyCustomer(
                    $order->user,
                    'Payment Successful - Action Required',
                    "Your payment for order #{$order->order_number} has been successfully processed. However, there was a technical issue creating your Google Drive folder. Our team has been notified. Please contact admin to get the folder link manually.",
                    $order
                );

                // --- Kirim notifikasi error ke admin ---
                $this->notificationService->notifyAdmin(
                    'Drive Folder Creation Failed',
                    "Order #{$order->order_number} was paid, but Google Drive folder creation failed. Error: " . $e->getMessage() . ". Please create the folder manually and provide the link to the customer.",
                    $order
                );

                // Tidak mengirim WhatsApp untuk error ini, atau kirim pesan error khusus jika diinginkan
            }
        }
    
        } elseif ($transactionStatus == 'expire') {
            if ($order->status === 'pending') { // Hanya update jika status masih pending
                $order->update([
                    'status' => 'cancelled', // Atau buat status khusus 'expired' jika diinginkan
                    'cancelled_at' => now() // Pastikan ada field cancelled_at di tabel orders
                ]);

                // Opsional: Kirim notifikasi ke customer
                $this->notificationService->notifyCustomer(
                    $order->user,
                    'Order Cancelled (Expired)',
                    "Your order #{$order->order_number} has been cancelled because the payment was not completed within the allocated time.",
                    $order
                );

                // Opsional: Notifikasi ke admin
                // $this->notificationService->notifyAdmin(...);
            }
        } elseif ($transactionStatus == 'cancel' || $transactionStatus == 'deny') { // Tambahkan 'deny' jika perlu
            if ($order->status === 'pending') { // Hanya update jika status masih pending
                $order->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now()
                ]);

                // Opsional: Kirim notifikasi ke customer
                $this->notificationService->notifyCustomer(
                    $order->user,
                    'Order Cancelled',
                    "Your order #{$order->order_number} has been cancelled.",
                    $order
                );
            }
        }

    }
}
