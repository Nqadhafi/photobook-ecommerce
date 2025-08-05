<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\PhotobookOrder;
use App\Models\Deskprint; // Model untuk deskprint
use App\Services\NotificationService; // Untuk mengirim notifikasi
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AdminOrderController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Validasi query parameters untuk filtering/pagination jika diperlukan
        $validated = $request->validate([
            'status' => 'nullable|string|in:pending,paid,file_upload,processing,ready,completed,cancelled',
            'customer_name' => 'nullable|string',
            // Tambahkan filter lain seperti date range, order_number
            'per_page' => 'nullable|integer|min:1|max:100', // Batasi jumlah item per halaman
        ]);

        $query = PhotobookOrder::with(['user', 'items.product', 'items.template']); // Eager load relasi yang sering dibutuhkan

        // Terapkan filter
        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }
        if (!empty($validated['customer_name'])) {
            // Asumsi ada relasi 'user' dan kolom 'name' di tabel users
            $query->whereHas('user', function ($q) use ($validated) {
                $q->where('name', 'like', '%' . $validated['customer_name'] . '%');
            });
        }
        // Tambahkan filter lain di sini...

        // Paginate hasil
        $perPage = $validated['per_page'] ?? 15; // Default 15 item per halaman
        $orders = $query->latest()->paginate($perPage);

        return response()->json($orders);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PhotobookOrder  $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(PhotobookOrder $order): JsonResponse
    {
        // Load relasi yang diperlukan untuk detail order
        $order->load(['user', 'items.product', 'items.template', 'payment']);
        return response()->json($order);
    }

    /**
     * Update the specified resource in storage.
     * (Untuk update status, bisa gunakan method khusus atau PATCH ke resource ini)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PhotobookOrder  $order
     * @return \Illuminate\Http\JsonResponse
     */
    // public function update(Request $request, PhotobookOrder $order): JsonResponse
    // {
    //     // Logika update umum untuk order (jika ada field yang bisa diupdate selain status)
    // }

    /**
     * Remove the specified resource from storage.
     * (Biasanya order tidak dihapus, tapi di-cancel. Tapi method ini disediakan oleh --api)
     *
     * @param  \App\Models\PhotobookOrder  $order
     * @return \Illuminate\Http\JsonResponse
     */
    // public function destroy(PhotobookOrder $order): JsonResponse
    // {
    //     // Logika hapus order
    // }

    /**
     * Update status order.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PhotobookOrder  $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, PhotobookOrder $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => [
                'required',
                'string',
                Rule::in(['pending', 'paid', 'file_upload', 'processing', 'ready', 'completed', 'cancelled']) // Sesuaikan dengan status yang diizinkan
            ],
            // Bisa tambahkan field lain seperti 'notes' untuk catatan admin
        ]);

        // Validasi transisi status? (Opsional, tergantung kompleksitas logika bisnis)
        // Misalnya, hanya bisa dari 'paid' ke 'file_upload', dll.

        $oldStatus = $order->status;
        $order->update(['status' => $validated['status']]);

        // Logika tambahan setelah update status?
        // Misalnya, kirim notifikasi ke customer jika status berubah ke 'processing'

        Log::info("Order ID {$order->id} status updated by admin", [
            'from' => $oldStatus,
            'to' => $validated['status'],
            'updated_by_user_id' => auth()->id() // ID admin yang melakukan update
        ]);

        return response()->json([
            'message' => 'Order status updated successfully.',
            'order' => $order->fresh() // Kembalikan data order yang sudah diperbarui
        ]);
    }

    /**
     * Send order details to a selected deskprint.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PhotobookOrder  $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendToDeskprint(Request $request, PhotobookOrder $order): JsonResponse
    {
        $validated = $request->validate([
            'deskprint_id' => 'required|exists:deskprints,id', // Validasi bahwa deskprint_id ada di tabel deskprints
        ]);

        $deskprint = Deskprint::findOrFail($validated['deskprint_id']);

        // Siapkan pesan detail order
        // Anda bisa membuat format pesan yang lebih kompleks atau menggunakan view/template
        $orderDetails = "New Order for Production:\n";
        $orderDetails .= "Order Number: {$order->order_number}\n";
        $orderDetails .= "Customer: {$order->customer_name}\n";
        $orderDetails .= "Customer Phone: {$order->customer_phone}\n";
        $orderDetails .= "Customer Address: {$order->customer_address}, {$order->customer_city}, {$order->customer_postal_code}\n";
        $orderDetails .= "Google Drive Folder: {$order->google_drive_folder_url}\n";
        $orderDetails .= "Items:\n";
        foreach ($order->items as $item) {
            $productName = $item->product ? $item->product->name : 'N/A';
            $templateName = $item->template ? $item->template->name : 'N/A';
            $orderDetails .= "- {$item->quantity}x {$productName} (Template: {$templateName})\n";
        }
        // Tambahkan catatan lain jika ada

        // Kirim notifikasi ke deskprint
        $isSent = $this->notificationService->sendOrderToDeskprint($deskprint, $orderDetails);

        if ($isSent) {
            // Simpan log atau update status order jika diperlukan
            // Misalnya, tambah kolom 'sent_to_deskprint_id' dan 'sent_to_deskprint_at' di tabel orders
            // $order->update([
            //     'sent_to_deskprint_id' => $deskprint->id,
            //     'sent_to_deskprint_at' => now()
            // ]);

            Log::info("Order ID {$order->id} details sent to Deskprint ID {$deskprint->id} ({$deskprint->name})", [
                'sent_by_user_id' => auth()->id()
            ]);

            return response()->json([
                'message' => 'Order details sent to deskprint successfully.',
                'order' => $order->fresh(), // Kembalikan data order yang diperbarui
                // 'deskprint' => $deskprint // Opsional: kembalikan info deskprint
            ]);
        } else {
            Log::error("Failed to send order ID {$order->id} details to Deskprint ID {$deskprint->id}", [
                'sent_by_user_id' => auth()->id()
            ]);
            return response()->json([
                'error' => 'Failed to send order details to deskprint. Please try again.'
            ], 500);
        }
    }

    /**
     * Get dashboard statistics for admin.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function dashboard(Request $request): JsonResponse
    {
         // Contoh statistik sederhana
        $stats = [
            'total_orders' => PhotobookOrder::count(),
            'orders_pending' => PhotobookOrder::where('status', 'pending')->count(),
            'orders_paid' => PhotobookOrder::where('status', 'paid')->count(),
            'orders_processing' => PhotobookOrder::where('status', 'processing')->count(),
            // Tambahkan statistik lain yang relevan
        ];

        return response()->json($stats);
    }
}
