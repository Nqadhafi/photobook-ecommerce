<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PhotobookCartController;
use App\Http\Controllers\PhotobookOrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\MidtransWebhookController;


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

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);

    Route::get('/cart', [PhotobookCartController::class, 'index']);
    Route::post('/cart', [PhotobookCartController::class, 'store']);
    Route::delete('/cart/{cart}', [PhotobookCartController::class, 'destroy']); 
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

// Route untuk Webhook Midtrans (TIDAK menggunakan auth middleware)
// Midtrans akan mengakses ini secara langsung
Route::post('/webhook/midtrans', [MidtransWebhookController::class, 'handle'])
    ->name('midtrans.webhook'); // Beri nama untuk kemudahan
