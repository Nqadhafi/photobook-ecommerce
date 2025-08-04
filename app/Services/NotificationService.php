<?php

namespace App\Services;

use App\Models\User;
use App\Models\PhotobookOrder;
use App\Models\PhotobookNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Kirim notifikasi ke customer.
     *
     * @param User $user
     * @param string $title
     * @param string $message
     * @param PhotobookOrder|null $order
     * @param string|null $actionUrl
     * @return void
     */
    public function notifyCustomer(User $user, string $title, string $message, ?PhotobookOrder $order = null, ?string $actionUrl = null): void
    {
        PhotobookNotification::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'is_read' => false,
        ]);

        // TODO: Kirim email jika diperlukan
        // Mail::to($user->email)->send(new OrderNotificationMail($title, $message, $actionUrl));
    }

    /**
     * Kirim notifikasi ke admin (contoh sederhana).
     * Untuk sekarang, kita bisa kirim ke user tertentu dengan role admin, atau ke email admin.
     * Untuk kemudahan, kita asumsikan ada user dengan email 'admin@example.com' atau role 'admin'.
     *
     * @param string $title
     * @param string $message
     * @param PhotobookOrder|null $order
     * @return void
     */
    public function notifyAdmin(string $title, string $message, ?PhotobookOrder $order = null): void
    {
        // Cari user admin (contoh sederhana)
        // Di production, gunakan sistem role/permission yang lebih robust.
        $adminUser = User::where('email', 'admin@test.com')->first(); // Sesuaikan dengan email admin kamu

        if ($adminUser) {
            PhotobookNotification::create([
                'user_id' => $adminUser->id,
                'order_id' => $order->id,
                'title' => "[ADMIN] " . $title,
                'message' => $message,
                'is_read' => false,
            ]);
            // TODO: Kirim email ke admin jika diperlukan
        } else {
            // Log jika admin user tidak ditemukan
            Log::warning("Admin user not found for notification: {$title}");
        }
    }

  public function sendWhatsAppMessage(string $phoneNumber, string $message): bool
{
    $whatsappApiUrl = env('WHATSAPP_API_URL');
    $whatsappApiToken = env('WHATSAPP_API_TOKEN');

    if (!$whatsappApiUrl || !$whatsappApiToken) {
        Log::error("Fonnte API credentials are missing in .env");
        return false;
    }

    try {
        // --- Contoh penyesuaian untuk Fonnte (hipotetis, sesuaikan dengan dokumentasi sebenarnya) ---
        $response = Http::withHeaders([
            'Authorization' => $whatsappApiToken, // Sesuaikan header autentikasi
        ])->post($whatsappApiUrl, [
            'target' => $phoneNumber, // Sesuaikan nama field
            'message' => $message,   // Sesuaikan nama field
            // 'delay' => '2', // Contoh parameter tambahan Fonnte
            // Tambahkan field lain sesuai dokumentasi Fonnte
        ]);
        // --- Akhir contoh penyesuaian ---

        if ($response->successful()) {
            Log::info("WhatsApp message sent successfully to {$phoneNumber} via Fonnte");
            return true;
        } else {
            Log::error("Failed to send WhatsApp message via Fonnte to {$phoneNumber}. Status: " . $response->status() . ". Body: " . $response->body());
            return false;
        }

    } catch (\Exception $e) {
        Log::error("Exception occurred while sending WhatsApp message via Fonnte to {$phoneNumber}: " . $e->getMessage());
        return false;
    }
}
    // Bisa tambahkan method notifyByEmail, notifyBySms, dll jika diperlukan
}
