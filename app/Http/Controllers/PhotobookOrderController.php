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
        if ($order->status !== 'paid') {
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

        try {
            // 4. Proses upload file
            foreach ($validatedData['files'] as $fileData) {
                $uploadedFile = $fileData['file'];
                $orderItemId = $fileData['order_item_id'];

                // Buat nama file unik
                $filename = uniqid() . '_' . time() . '.' . $uploadedFile->getClientOriginalExtension();

                // Simpan file ke disk 'public' di folder 'uploads/files'
                // Disk 'public' harus dikonfigurasi di config/filesystems.php
                $path = $uploadedFile->storeAs('uploads/files', $filename, 'public');

                // 5. Simpan informasi file ke database
                PhotobookOrderFile::create([
                    'order_id' => $order->id,
                    'order_item_id' => $orderItemId,
                    'file_path' => $path, // Path relatif dari disk 'public'
                    'status' => 'uploaded', // Atau 'confirmed' jika tidak perlu review admin
                    'uploaded_at' => now(),
                ]);
                $this->notificationService->notifyCustomer(
                    $request->user(),
                    'File Uploaded',
                    "Your design file(s) for order #{$order->order_number} have been uploaded successfully.",
                    $order
                );
                $this->notificationService->notifyAdmin(
                    'Files Uploaded for Order',
                    "New files have been uploaded for order #{$order->order_number}. Please review them.",
                    $order
                );
            }

            // 6. (Opsional) Update status order
            // Misalnya, jika semua item sudah punya file, ubah status ke 'file_upload' atau 'processing'
            // Untuk sekarang, kita tidak ubah status dulu.
            // Bisa ditambahkan logika ini nanti.

            // 7. Kembalikan response sukses
            return response()->json(['message' => 'Files uploaded successfully'], 200);
        } catch (\Exception $e) {
            // Log error jika perlu
            Log::error('File Upload Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to upload files. Please try again.'], 500);
        }
    }
}
