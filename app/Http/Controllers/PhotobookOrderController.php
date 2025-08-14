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
            'notes'       => ['nullable', 'string', 'max:1000'],
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

            // --- Akhir Validasi per user ---
        }
        // --- Akhir Validasi Kupon ---

        // --- 3. Ambil item cart user ---
        $cartItems = $user->photobookCarts()->with(['product', 'template'])->get();

        // --- 4. Validasi cart tidak kosong ---
        if ($cartItems->isEmpty()) {
            return response()->json(['error' => 'Your cart is empty'], 400);
        }


        // --- 5. Hitung subtotal ---
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
            return DB::transaction(function () use ($user, $cartItems, $subTotalAmount, $discountAmount, $totalAmount, $coupon,$request) {
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
                    'notes'             => $request->input('notes'),
                    
                ];


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
