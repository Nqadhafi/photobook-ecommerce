<?php

namespace App\Http\Controllers;

use App\Services\MidtransService;
use App\Models\PhotobookMidtransPayment;
use App\Models\PhotobookOrder;
use App\Models\PhotobookOrderItem;
use App\Models\PhotobookOrderFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Services\NotificationService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB; // Untuk transaction

class PhotobookOrderController extends Controller
{

    protected $midtransService;
    protected $notificationService;
    public function __construct(MidtransService $midtransService, NotificationService $notificationService) // Dependency Injection
    {
        $this->midtransService = $midtransService;
        $this->notificationService = $notificationService;
    }
    /**
     * Handle the checkout process.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkout(Request $request): JsonResponse
    {
        $user = $request->user();

        // 1. Ambil item cart user
        $cartItems = $user->photobookCarts()->with(['product', 'template'])->get();

        // 2. Validasi cart tidak kosong
        if ($cartItems->isEmpty()) {
            return response()->json(['error' => 'Your cart is empty'], 400);
        }

        // 3. Validasi profil customer ada (opsional untuk versi awal)
        // Kita asumsikan sudah ada dari test setup

        // 4. Hitung total
        $totalAmount = 0;
        foreach ($cartItems as $item) {
            // Validasi tambahan: pastikan produk dan template aktif
            // ... (sama seperti sebelumnya)
            $totalAmount += $item->quantity * $item->product->price;
        }

        // 5. Buat order dalam transaction database
        try {
            return DB::transaction(function () use ($user, $cartItems, $totalAmount) {
                // Generate order number unik
                $orderNumber = 'PB' . strtoupper(uniqid());

                // Buat order
                $order = \App\Models\PhotobookOrder::create([
                    'user_id' => $user->id,
                    'order_number' => $orderNumber,
                    'total_amount' => $totalAmount,
                    'status' => 'pending',
                    'customer_name' => $user->name,
                    'customer_email' => $user->email,
                    'customer_phone' => $user->photobookProfile->phone_number ?? '',
                    'customer_address' => $user->photobookProfile->address ?? '',
                    'customer_city' => $user->photobookProfile->city ?? '',
                    'customer_postal_code' => $user->photobookProfile->postal_code ?? '',
                    'pickup_code' => strtoupper(substr(md5(uniqid()), 0, 6)),
                ]);

                // Buat order items
                foreach ($cartItems as $item) {
                    $order->items()->create([
                        'product_id' => $item->product_id,
                        'template_id' => $item->template_id,
                        'quantity' => $item->quantity,
                        'price' => $item->product->price,
                        'design_same' => $item->design_same,
                    ]);
                }

                // Kosongkan cart
                $user->photobookCarts()->delete();
                $this->notificationService->notifyCustomer(
                    $user,
                    'Order Created',
                    "Your order #{$order->order_number} has been created. Please proceed with the payment.",
                    $order
                    // Tidak ada action URL khusus untuk pembayaran, karena Snap token dikirim di response
                );
                // 6. Integrasi Midtrans: Buat transaksi
                $midtransResponse = $this->midtransService->createTransaction($order);

                if ($midtransResponse['status'] === 'error') {
                    // Log error
                    Log::error('Midtrans Transaction Error: ' . $midtransResponse['message']);
                    // Bisa rollback transaction atau tetap buat order dan beri instruksi manual
                    // Untuk sekarang, kita return error
                    return response()->json(['error' => 'Failed to initiate payment. Please try again.'], 500);
                }

                // 7. Simpan detail pembayaran ke tabel photobook_midtrans_payments
                PhotobookMidtransPayment::create([
                    'order_id' => $order->id,
                    'snap_token' => $midtransResponse['snap_token'],
                    // redirect_url bisa dibentuk di frontend dari snap_token
                    // atau bisa disimpan jika menggunakan vtweb
                    'redirect_url' => '', // Isi jika menggunakan vtweb, atau biarkan kosong
                    'customer_name' => $order->customer_name,
                    'customer_email' => $order->customer_email,
                    'customer_phone' => $order->customer_phone,
                    'customer_address' => $order->customer_address,
                    'customer_city' => $order->customer_city,
                    // Field lain akan diisi saat notifikasi diterima
                ]);

                // Load items untuk response
                $order->load('items');

                // 8. Kembalikan response dengan snap_token
                return response()->json([
                    'order' => $order,
                    'snap_token' => $midtransResponse['snap_token'],
                    // 'redirect_url' => $midtransResponse['redirect_url'] ?? null, // Jika pakai vtweb
                ], 201);
            });
        } catch (\Exception $e) {
            // DB::rollBack(); // Tidak perlu karena sudah pakai DB::transaction closure
            Log::error('Checkout Exception: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to process checkout. Please try again.'], 500);
        }
    }

    public function uploadFiles(Request $request, PhotobookOrder $order): JsonResponse
    {
        // 1. Authorization: Pastikan user memiliki order ini
        if ($request->user()->id !== $order->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // 2. Validasi status order
        // Izinkan upload untuk status 'paid' dan 'file_upload'
        if (!in_array($order->status, ['paid', 'file_upload'])) {
            return response()->json(['error' => 'Order is not eligible for file upload.'], 400);
        }

        // 3. Validasi input
        $validator = Validator::make($request->all(), [
            'files' => 'required|array|min:1',
            'files.*.order_item_id' => [
                'required',
                'integer',
                // Validasi bahwa order_item_id milik order ini
                Rule::exists('photobook_order_items', 'id')->where(function ($query) use ($order) {
                    $query->where('order_id', $order->id);
                }),
            ],
            'files.*.file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240', // Max 10MB
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();
        $uploadedFiles = []; // Untuk menyimpan data file yang berhasil diupload
        $filesToProcess = $validatedData['files'];

        try {
            // 4. Proses upload file dalam transaksi database untuk konsistensi
            $uploadedFiles = DB::transaction(function () use ($filesToProcess, $order) {
                $uploadedInTransaction = [];

                foreach ($filesToProcess as $fileData) {
                    $uploadedFile = $fileData['file'];
                    $orderItemId = $fileData['order_item_id'];

                    // Buat nama file unik
                    $filename = uniqid() . '_' . time() . '.' . $uploadedFile->getClientOriginalExtension();

                    // --- UPLOAD KE CLOUDFLARE R2 ---
                    // Simpan file ke disk 'r2' yang telah dikonfigurasi
                    $pathInR2 = $uploadedFile->storeAs('files', $filename, 'r2');
                    // Contoh nilai $pathInR2: 'files/abc123_1678886400.jpg'
                    // File sebenarnya di R2: 'uploads/files/abc123_1678886400.jpg' (karena config 'root' => 'uploads')

                    // --- SIMPAN INFORMASI FILE KE DATABASE ---
                    $fileRecord = PhotobookOrderFile::create([
                        'order_id' => $order->id,
                        'order_item_id' => $orderItemId,
                        'file_path' => $pathInR2, // Simpan path relatif dari root bucket R2
                        // Opsional: Simpan informasi tambahan
                        'original_name' => $uploadedFile->getClientOriginalName(),
                        'size' => $uploadedFile->getSize(),
                        'mime_type' => $uploadedFile->getMimeType(),
                        'status' => 'uploaded', // Atau 'confirmed' jika tidak perlu review admin
                        'uploaded_at' => now(),
                        // Jika Anda menyimpan URL publik di .env R2_URL:
                        // 'public_url' => rtrim(env('R2_URL'), '/') . '/uploads/' . basename($pathInR2),
                    ]);

                    // Tambahkan ke array hasil sukses
                    // Mengembalikan data yang relevan untuk frontend, termasuk ID record database
                    $uploadedInTransaction[] = [
                        'id' => $fileRecord->id, // ID dari record database
                        'order_item_id' => $orderItemId,
                        'original_name' => $fileRecord->original_name,
                        'size' => $fileRecord->size,
                        'file_path' => $fileRecord->file_path, // Path di R2
                        // 'public_url' => $fileRecord->public_url, // Jika disimpan
                    ];
                }

                return $uploadedInTransaction;
            });

            // 5. Kirim notifikasi setelah semua file berhasil diupload (di luar transaksi)
            if (count($uploadedFiles) > 0) {
                $this->notificationService->notifyCustomer(
                    $request->user(),
                    'File(s) Uploaded',
                    count($uploadedFiles) . " design file(s) for order #{$order->order_number} have been uploaded successfully.",
                    $order
                );
                $this->notificationService->notifyAdmin(
                    'Files Uploaded for Order',
                    count($uploadedFiles) . " new file(s) have been uploaded for order #{$order->order_number}. Please review them.",
                    $order
                );
            }

            // 6. (Opsional) Update status order jika semua item sudah punya file
            // Logika ini bisa disesuaikan. Contoh sederhana:
            // Jika status awal 'paid' dan berhasil upload, ubah ke 'file_upload'
            if ($order->status === 'paid' && count($uploadedFiles) > 0) {
                 // Anda bisa menambahkan logika yang lebih kompleks di sini
                 // Misalnya, periksa apakah semua item dalam order ini sudah memiliki file.
                 // Untuk sekarang, kita asumsikan bahwa berhasil upload berarti siap untuk diproses.
                 $order->update(['status' => 'file_upload']);
                 // Atau langsung ke 'processing' jika memang sesuai logika bisnis Anda:
                 // $order->update(['status' => 'processing']);
            }
            // Jika status sudah 'file_upload', tidak perlu diubah lagi.

            // 7. Kembalikan response sukses dengan data file yang diupload
            return response()->json([
                'message' => count($uploadedFiles) . ' file(s) uploaded successfully',
                'uploaded_count' => count($uploadedFiles),
                'uploaded_files' => $uploadedFiles // Data file yang berhasil
            ], 200);

        } catch (\Exception $e) {
            // 8. Penanganan error: Log error dengan detail
            Log::error('File Upload Error (R2): ' . $e->getMessage(), [
                'order_id' => $order->id,
                'user_id' => $request->user()->id,
                'exception' => $e
            ]);

            // Kembalikan error response yang umum
            // Frontend dapat menggunakan ini untuk menawarkan retry.
            return response()->json([
                'error' => 'Failed to upload one or more files. Please try again.',
                // Jika ingin lebih spesifik, bisa mengembalikan daftar file yang gagal
                // tapi itu memerlukan logika pelacakan yang lebih kompleks di dalam loop.
                // Untuk sekarang, pendekatan umum sudah cukup untuk trigger retry di frontend.
            ], 500);
        }
    }

        public function cancelOrder(Request $request, PhotobookOrder $order): JsonResponse
    {
        $user = $request->user();

        // 1. Authorization: Pastikan user memiliki order ini
        if ($user->id !== $order->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // 2. Validasi status order: Hanya bisa dibatalkan jika statusnya 'pending'
        if ($order->status !== 'pending') {
            return response()->json(['error' => 'Order cannot be cancelled at this stage.'], 400);
        }

        try {
            // 3. Update status order dalam transaction database untuk konsistensi
            // Misalnya, jika ada logika tambahan atau notifikasi yang kompleks
            return DB::transaction(function () use ($order, $user) {
                $order->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);

                // 4. (Opsional) Update status pembayaran terkait jika ada dan perlu
                // Misalnya, tandai bahwa pembayaran dibatalkan di sisi merchant
                $payment = $order->payment; // Asumsi relasi 'payment' ada
                if ($payment) {
                    $payment->update(['transaction_status' => 'cancel']);
                }

                // 5. Kirim notifikasi ke customer
                $this->notificationService->notifyCustomer(
                    $user,
                    'Order Cancelled',
                    "Your order #{$order->order_number} has been successfully cancelled.",
                    $order
                );

                // 6. (Opsional) Kirim notifikasi ke admin
                $this->notificationService->notifyAdmin(
                    'Order Cancelled by User',
                    "Order #{$order->order_number} has been cancelled by the customer.",
                    $order
                );

                // 7. Kembalikan response sukses
                 // Load relasi yang mungkin dibutuhkan oleh frontend setelah pembatalan
                $order->load('items'); // Atau relasi lain yang relevan

                return response()->json([
                    'message' => 'Order cancelled successfully.',
                    'data' => $order // Kembalikan data order yang sudah diperbarui
                ], 200);
            });
        } catch (\Exception $e) {
            Log::error('Order Cancellation Exception: ' . $e->getMessage(), ['order_id' => $order->id, 'user_id' => $user->id]);
            return response()->json(['error' => 'Failed to cancel order. Please try again.'], 500);
        }
    }
}
