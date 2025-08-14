<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderHistoryController;
use App\Http\Controllers\PhotobookCartController;
use App\Http\Controllers\PhotobookOrderController;
use App\Http\Controllers\MidtransWebhookController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminTemplateController;
use App\Http\Controllers\Admin\AdminCouponController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\Admin\AdminDeskprintController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::get('/', function () {
    return response()->json(['message' => 'API is running']);
});

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::get('/products/{product}/templates', [ProductController::class, 'templates']);
Route::get('/templates/{template}', [TemplateController::class, 'show']);

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// --- Route API Admin ---
Route::prefix('admin')->middleware(['auth:sanctum'])->group(function () { // Middleware auth:sanctum diterapkan ke seluruh grup
    // Routes untuk Admin dan Super Admin
    Route::get('/dashboard', [AdminOrderController::class, 'dashboard']); // Contoh endpoint dashboard
    Route::apiResource('orders', AdminOrderController::class); // GET /api/admin/orders, GET /api/admin/orders/{order}, dll.
    // Tambahkan endpoint khusus untuk order jika diperlukan
    Route::post('/orders/{order}/update-status', [AdminOrderController::class, 'updateStatus']); // Atau gunakan PATCH pada resource
    Route::post('/orders/{order}/send-to-deskprint', [AdminOrderController::class, 'sendToDeskprint']);
    // Tambahkan endpoint untuk melihat file di GDrive jika diperlukan (kompleks, mungkin lebih baik di frontend dengan link langsung)
    
    // Routes khusus Super Admin
    Route::middleware(['role:super_admin'])->group(function () {
        Route::apiResource('users', AdminUserController::class); // Hanya Super Admin
        Route::apiResource('products', AdminProductController::class); // Hanya Super Admin
        Route::apiResource('templates', AdminTemplateController::class); // Hanya Super Admin
        Route::apiResource('coupons', AdminCouponController::class); // Hanya Super Admin
        Route::apiResource('deskprints', AdminDeskprintController::class); // Hanya Super Admin
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);

    Route::get('/cart', [PhotobookCartController::class, 'index']);
    Route::post('/cart', [PhotobookCartController::class, 'store']);
    Route::delete('/cart/{cart}', [PhotobookCartController::class, 'destroy']); 
    Route::put('/cart/{cart}', [PhotobookCartController::class, 'update']);
// Route untuk Checkout 
    Route::post('/checkout', [PhotobookOrderController::class, 'checkout']); 
    // --- Route baru untuk Upload File ---
    // Route untuk mengupload file ke order tertentu
    Route::post('/orders/{order}/upload', [PhotobookOrderController::class, 'uploadFiles']);
    // -------------------------------------
        // --- Route baru untuk Notifikasi ---
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
});
    // Order History Routes
    Route::get('/orders', [OrderHistoryController::class, 'index']);
    Route::get('/orders/{order}', [OrderHistoryController::class, 'show']);
    Route::get('/orders/{order}/timeline', [OrderHistoryController::class, 'timeline']);
    Route::post('/orders/{order}/cancel', [PhotobookOrderController::class, 'cancelOrder']);
    Route::post('/validate-coupon', [CouponController::class, 'validateCoupon']);
// Route untuk Webhook Midtrans (TIDAK menggunakan auth middleware)
// Midtrans akan mengakses ini secara langsung
Route::post('/webhook/midtrans', [MidtransWebhookController::class, 'handle'])
    ->name('midtrans.webhook'); // Beri nama untuk kemudahan
