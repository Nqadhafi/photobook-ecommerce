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
use App\Models\Coupon;


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

        // --- 1. Validasi input termasuk kode kupon ---
        $validator = Validator::make($request->all(), [
            'coupon_code' => ['nullable', 'string', 'exists:coupons,code'], // Validasi dasar kode kupon
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $couponCode = $request->input('coupon_code');
        $coupon = null;

        // --- 2. Temukan dan validasi kupon jika kode diberikan ---
        if ($couponCode) {
            // Coba temukan kupon yang aktif dan valid
            $coupon = Coupon::active()->valid()->where('code', $couponCode)->first();

            if (!$coupon) {
                return response()->json(['error' => 'Invalid, expired, or inactive coupon code.'], 400);
            }

            // Gunakan method isUsable dari model Coupon untuk pengecekan akhir
            // Ini mencakup cek aktif, tanggal, dan batas penggunaan total
            if (!$coupon->isUsable()) {
                return response()->json(['error' => 'This coupon is no longer available or has reached its usage limit.'], 400);
            }

            // --- Validasi tambahan: Cek penggunaan per user jika diperlukan ---
            // Misalnya, jika Anda memiliki relasi atau tabel untuk melacak penggunaan per user:
            /*
            if ($coupon->max_uses_per_user > 0) {
                // Asumsi: ada relasi 'orders' di model User yang mengarah ke PhotobookOrder
                // dan relasi 'coupons' di PhotobookOrder (many-to-many)
                $userCouponUsageCount = $user->orders()->whereHas('coupons', function($query) use ($coupon) {
                    $query->where('coupon_id', $coupon->id);
                })->count();

                if ($userCouponUsageCount >= $coupon->max_uses_per_user) {
                    return response()->json(['error' => 'You have reached the maximum usage limit for this coupon.'], 400);
                }
            }
            */
            // --- Akhir Validasi per user ---
        }
        // --- Akhir Validasi Kupon ---

        // --- 3. Ambil item cart user ---
        $cartItems = $user->photobookCarts()->with(['product', 'template'])->get();

        // --- 4. Validasi cart tidak kosong ---
        if ($cartItems->isEmpty()) {
            return response()->json(['error' => 'Your cart is empty'], 400);
        }

        // --- 5. Validasi profil customer ada (opsional untuk versi awal) ---
        // Kita asumsikan sudah ada dari test setup
        // Tambahkan pengecekan keberadaan profil jika diperlukan:
        /*
        if (!$user->photobookProfile) {
             return response()->json(['error' => 'Customer profile information is missing. Please update your profile.'], 400);
        }
        */

        // --- 6. Hitung subtotal ---
        $subTotalAmount = 0;
        $hasInvalidItem = false; // Flag untuk item yang tidak valid
        foreach ($cartItems as $item) {
            // Validasi tambahan: pastikan produk dan template ada dan aktif jika diperlukan
            // Contoh sederhana: cek apakah relasi dimuat dan memiliki harga
            if (!$item->product || !isset($item->product->price)) {
                Log::warning('Invalid product in cart for user ID: ' . $user->id, ['cart_item_id' => $item->id]);
                $hasInvalidItem = true;
                // Anda bisa memilih untuk return error atau skip item ini
                // Untuk sekarang, kita akhiri proses
                return response()->json(['error' => 'One or more items in your cart are invalid. Please review your cart.'], 400);
            }
            // Bisa juga mengecek $item->template jika diperlukan
            $subTotalAmount += $item->quantity * $item->product->price;
        }

        // --- 7. Hitung diskon ---
        $discountAmount = 0;
        if ($coupon) {
            // Hitung diskon berdasarkan persentase
            // Pastikan discount_percent tidak null
            if ($coupon->discount_percent !== null) {
                $discountAmount = ($coupon->discount_percent / 100) * $subTotalAmount;
            }
            // Jika menggunakan diskon nominal, ganti blok di atas dengan:
            // if ($coupon->discount_amount !== null) {
            //     $discountAmount = min($coupon->discount_amount, $subTotalAmount); // Diskon tidak boleh melebihi subtotal
            // }
        }

        // --- 8. Hitung total akhir ---
        $totalAmount = max($subTotalAmount - $discountAmount, 0); // Total tidak boleh negatif

        // --- 9. Buat order dalam transaction database ---
        try {
            return DB::transaction(function () use ($user, $cartItems, $subTotalAmount, $discountAmount, $totalAmount, $coupon) {
                // --- Generate order number unik ---
                $orderNumber = 'PB' . strtoupper(uniqid());

                // --- Buat order dengan informasi kupon dan diskon ---
                // Pastikan kolom 'sub_total_amount' dan 'discount_amount' ada di tabel photobook_orders
                $orderData = [
                    'user_id' => $user->id,
                    'order_number' => $orderNumber,
                    'sub_total_amount' => $subTotalAmount, // Kolom baru
                    'discount_amount' => $discountAmount,  // Kolom baru
                    'total_amount' => $totalAmount, // Total yang sudah dikurangi diskon
                    'status' => 'pending',
                    'customer_name' => $user->name ?? '', // Tambahkan penanganan null jika diperlukan
                    'customer_email' => $user->email ?? '',
                    'customer_phone' => $user->photobookProfile->phone_number ?? '',
                    'customer_address' => $user->photobookProfile->address ?? '',
                    'customer_city' => $user->photobookProfile->city ?? '',
                    'customer_postal_code' => $user->photobookProfile->postal_code ?? '',
                    'pickup_code' => strtoupper(substr(md5(uniqid()), 0, 6)),
                ];

                // Jika Anda menyimpan ID kupon di tabel orders (misalnya, kolom coupon_id):
                // if ($coupon) {
                //     $orderData['coupon_id'] = $coupon->id;
                // }

                $order = \App\Models\PhotobookOrder::create($orderData);

                // Periksa apakah order berhasil dibuat
                if (!$order) {
                     throw new \Exception('Failed to create order record.');
                }
                // --- Akhir Buat Order ---

                // --- Buat order items ---
                foreach ($cartItems as $item) {
                    // Validasi kembali sebelum membuat item order
                    if (!$item->product_id || !$item->template_id) {
                        Log::error('Missing product_id or template_id in cart item for order creation.', ['cart_item_id' => $item->id, 'order_id' => $order->id]);
                        throw new \Exception('Invalid item data in cart.');
                    }

                    $orderItem = $order->items()->create([
                        'product_id' => $item->product_id,
                        'template_id' => $item->template_id,
                        'quantity' => $item->quantity,
                        'price' => $item->product->price, // Harga pada saat order dibuat
                        'design_same' => $item->design_same,
                    ]);

                    if (!$orderItem) {
                         Log::error('Failed to create order item.', ['cart_item_id' => $item->id, 'order_id' => $order->id]);
                         throw new \Exception('Failed to create order items.');
                    }
                }
                // --- Akhir Buat Order Items ---

                // --- Kosongkan cart ---
                $deletedCartCount = $user->photobookCarts()->delete();
                
                // Tidak perlu dicek secara ketat, karena cart kosong juga tidak masalah
                // --- Akhir Kosongkan Cart ---

                // --- Kirim notifikasi ---
                $notificationMessage = "Your order #{$order->order_number} has been created. Please proceed with the payment.";
                if ($coupon && $discountAmount > 0) {
                    $notificationMessage .= " Coupon '{$coupon->code}' applied for a discount of Rp " . number_format($discountAmount, 0, ',', '.') . ".";
                }
                $this->notificationService->notifyCustomer(
                    $user,
                    'Order Created',
                    $notificationMessage,
                    $order
                );
                // --- Akhir Notifikasi ---

                // --- Integrasi Midtrans: Buat transaksi ---
                // Gunakan $totalAmount yang sudah dikurangi diskon
                $midtransResponse = $this->midtransService->createTransaction($order);

                if ($midtransResponse['status'] === 'error') {
                    // Log error
                    Log::error('Midtrans Transaction Error: ' . $midtransResponse['message'], ['order_id' => $order->id]);
                    // Bisa rollback transaction atau tetap buat order dan beri instruksi manual
                    // Untuk sekarang, kita lempar exception agar transaction di-rollback
                    throw new \Exception('Failed to initiate payment with Midtrans: ' . $midtransResponse['message']);
                }
                // --- Akhir Integrasi Midtrans ---

                // --- Simpan detail pembayaran ke tabel photobook_midtrans_payments ---
                $paymentRecord = PhotobookMidtransPayment::create([
                    'order_id' => $order->id,
                    'snap_token' => $midtransResponse['snap_token'],
                    'redirect_url' => '', // Isi jika menggunakan vtweb, atau biarkan kosong
                    'customer_name' => $order->customer_name,
                    'customer_email' => $order->customer_email,
                    'customer_phone' => $order->customer_phone,
                    'customer_address' => $order->customer_address,
                    'customer_city' => $order->customer_city,
                    // Field lain akan diisi saat notifikasi diterima
                ]);

                 if (!$paymentRecord) {
                     Log::error('Failed to create Midtrans payment record.', ['order_id' => $order->id]);
                     throw new \Exception('Failed to record payment details.');
                 }
                // --- Akhir Simpan Pembayaran ---

                // --- (Opsional) Tautkan kupon ke order (jika menggunakan relasi many-to-many) ---
                if ($coupon) {
                    try {
                        // Tautkan kupon ke order menggunakan relasi many-to-many
                        $order->coupons()->attach($coupon->id); // Menggunakan relasi coupons()

                        // Naikkan jumlah penggunaan kupon secara aman
                        // Menggunakan update agar aman dari race condition
                        DB::table('coupons')
                            ->where('id', $coupon->id)
                            ->increment('times_used');

                        // (Opsional) Catat penggunaan per user jika diperlukan dan menggunakan tabel terpisah
                        // DB::table('coupon_user')->insert([
                        //     'coupon_id' => $coupon->id,
                        //     'user_id' => $user->id,
                        //     'order_id' => $order->id,
                        //     'created_at' => now(),
                        //     'updated_at' => now(),
                        // ]);

                    } catch (\Exception $e) {
                        // Jika gagal menautkan kupon, log error tetapi jangan batalkan order
                        // Karena pembayaran sudah diinisiasi
                        Log::warning('Failed to link coupon to order or increment usage.', [
                            'order_id' => $order->id,
                            'coupon_id' => $coupon->id,
                            'error' => $e->getMessage()
                        ]);
                        // Bisa memilih untuk melempar exception jika ini kritis
                        // throw new \Exception('Failed to apply coupon to order.');
                    }
                }
                // --- Akhir Tautan Kupon ---

                // --- Load items untuk response ---
                $order->load('items'); // Eager load items
                // --- Akhir Load Items ---

                // --- 10. Kembalikan response dengan snap_token dan info kupon ---
                $responseData = [
                    'order' => $order,
                    'snap_token' => $midtransResponse['snap_token'],
                    // 'redirect_url' => $midtransResponse['redirect_url'] ?? null, // Jika pakai vtweb
                ];

                // Sertakan info kupon yang diterapkan jika ada
                if ($coupon) {
                    $responseData['coupon_applied'] = [
                        'code' => $coupon->code,
                        'discount_percent' => $coupon->discount_percent, // Atau discount_amount jika digunakan
                        'discount_value' => $discountAmount,
                    ];
                }

                return response()->json($responseData, 201);
                // --- Akhir Response ---
            });
        } catch (\Exception $e) {
            // DB::rollBack(); // Tidak perlu karena sudah pakai DB::transaction closure
            Log::error('Checkout Exception: ' . $e->getMessage(), [
                'user_id' => $user->id ?? null,
                'coupon_code' => $couponCode ?? null,
                'exception' => $e
            ]);
            // Periksa jenis exception untuk memberikan pesan yang lebih spesifik
            if (strpos($e->getMessage(), 'Midtrans') !== false) {
                return response()->json(['error' => 'Failed to initiate payment. Please try again.'], 500);
            } elseif (strpos($e->getMessage(), 'order record') !== false || strpos($e->getMessage(), 'order items') !== false) {
                 return response()->json(['error' => 'Failed to create order. Please try again.'], 500);
            } else {
                return response()->json(['error' => 'Failed to process checkout. Please try again.'], 500);
            }
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
