<?php

namespace App\Http\Controllers;

use App\Services\MidtransService; // Tambahkan ini
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log; // Tambahkan ini

class MidtransWebhookController extends Controller
{
    protected $midtransService;

    public function __construct(MidtransService $midtransService) // Dependency Injection
    {
        $this->midtransService = $midtransService;
    }

    /**
     * Handle the incoming webhook notification from Midtrans.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function handle(Request $request): Response
    {
        // 1. Terima notifikasi dari Midtrans (dalam bentuk JSON POST)
        $notificationJson = $request->getContent();
        $notification = json_decode($notificationJson, true);

        Log::info('Midtrans Webhook Notification Received', ['notification' => $notification]);

        // 2. Verifikasi signature key untuk keamanan (opsional tapi direkomendasikan)
        // Kita bisa tambahkan verifikasi signature key di sini
        // Referensi: https://docs.midtrans.com/en/after-payment/http-notification?id=security

        // 3. Proses notifikasi menggunakan service
        $this->midtransService->handleNotification($notification);

        // 4. Kembalikan response 200 OK ke Midtrans
        return response('', 200);
    }
}
