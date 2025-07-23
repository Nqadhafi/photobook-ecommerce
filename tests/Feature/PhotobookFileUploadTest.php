<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\PhotobookProduct;
use App\Models\PhotobookTemplate;
use App\Models\PhotobookOrder;
use App\Models\PhotobookOrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;


class PhotobookFileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $order;
    protected $orderItem;

    protected function setUp(): void
    {
        parent::setUp();

        // Membuat user, produk, template, order, dan order item untuk testing
        $this->user = User::factory()->create();
        $product = PhotobookProduct::factory()->create(['price' => 150000]);
        $template = PhotobookTemplate::factory()->create(['product_id' => $product->id]);

        // Buat order dengan status 'paid'
        $this->order = PhotobookOrder::factory()->paid()->create([
            'user_id' => $this->user->id,
            'order_number' => 'PB-TEST-UPLOAD-001',
            'total_amount' => 150000,
        ]);

        // Buat satu order item
        $this->orderItem = PhotobookOrderItem::factory()->create([
            'order_id' => $this->order->id,
            'product_id' => $product->id,
            'template_id' => $template->id,
            'quantity' => 1,
            'price' => 150000,
        ]);
    }

    // Test 1: User terautentikasi bisa mengupload file untuk ordernya yang paid
    /** @test */
    public function authenticated_user_can_upload_files_for_their_paid_order()
    {
        // Membuat file dummy untuk diupload
        Storage::fake('public'); // Mock disk 'public' untuk testing
        $file = UploadedFile::fake()->image('desain_foto.jpg'); // Membuat file gambar palsu

        $response = $this->actingAs($this->user)->postJson("/api/orders/{$this->order->id}/upload", [
            'files' => [
                [
                    'order_item_id' => $this->orderItem->id,
                    'file' => $file,
                ]
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Files uploaded successfully']);

        // Assert file disimpan di storage
        // Nama file akan di-hash, jadi kita cari file yang mirip
        Storage::disk('public')->assertExists('uploads/files/'); // Sesuaikan path jika perlu

        // Assert record dibuat di database
        $this->assertDatabaseHas('photobook_order_files', [
            'order_id' => $this->order->id,
            'order_item_id' => $this->orderItem->id,
            'status' => 'uploaded', // Atau 'confirmed' jika langsung dikonfirmasi
            // 'file_path' => // Sulit dicek karena nama file di-hash
        ]);

        // Assert status order berubah (opsional, tergantung logika)
        // $this->order->refresh();
        // $this->assertEquals('file_upload', $this->order->status); // Atau 'processing'
    }

    // Test 2: User tidak bisa mengupload file untuk order yang bukan miliknya
    /** @test */
    public function user_cannot_upload_files_for_another_users_order()
    {
        $anotherUser = User::factory()->create();
        $response = $this->actingAs($anotherUser)->postJson("/api/orders/{$this->order->id}/upload", [
            'files' => [
                [
                    'order_item_id' => $this->orderItem->id,
                    'file' => UploadedFile::fake()->image('desain_foto.jpg'),
                ]
            ]
        ]);

        // Bisa jadi 403 Forbidden atau 404 Not Found, tergantung implementasi
        // Kita pilih 403 Forbidden untuk kasus ini
        $response->assertStatus(403);
    }

    // Test 3: User tidak bisa mengupload file untuk order yang belum paid
    /** @test */
    public function user_cannot_upload_files_for_non_paid_order()
    {
        // Buat order dengan status pending
        $pendingOrder = PhotobookOrder::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/orders/{$pendingOrder->id}/upload", [
            'files' => [
                [
                    'order_item_id' => $this->orderItem->id, // Ini akan gagal validasi juga karena bukan item dari $pendingOrder
                    'file' => UploadedFile::fake()->image('desain_foto.jpg'),
                ]
            ]
        ]);

        // Harusnya gagal, bisa jadi 400 Bad Request atau 422 Unprocessable Entity
        // Kita pilih 400 untuk sekarang
        $response->assertStatus(400);
        $response->assertJson(['error' => 'Order is not eligible for file upload.']);
    }

    // Test 4: User harus login untuk mengupload file
    /** @test */
    public function guest_cannot_upload_files()
    {
        $response = $this->postJson("/api/orders/{$this->order->id}/upload", [
            'files' => [
                [
                    'order_item_id' => $this->orderItem->id,
                    'file' => UploadedFile::fake()->image('desain_foto.jpg'),
                ]
            ]
        ]);

        $response->assertStatus(401); // Unauthorized
    }

    // Test 5: Validasi input diperlukan
    /** @test */
    public function uploading_files_requires_valid_data()
    {
        $response = $this->actingAs($this->user)->postJson("/api/orders/{$this->order->id}/upload", [
            // Tidak ada data files
        ]);

        $response->assertStatus(422); // Unprocessable Entity
        // Bisa tambahkan assertion untuk error spesifik jika diperlukan
        // $response->assertJsonValidationErrors(['files']);
    }
}
