<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\PhotobookProduct;
use App\Models\PhotobookTemplate;
use App\Models\PhotobookCart;
use App\Models\PhotobookCustomerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class PhotobookCheckoutTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $product;
    protected $template;

    protected function setUp(): void
    {
        parent::setUp();

        // Membuat user, produk, template, dan profil untuk testing
        $this->user = User::factory()->create();
        $this->product = PhotobookProduct::factory()->create(['price' => 150000, 'is_active' => true]);
        $this->template = PhotobookTemplate::factory()->create([
            'product_id' => $this->product->id,
            'is_active' => true
        ]);

        // Buat profil customer
        PhotobookCustomerProfile::factory()->create([
            'user_id' => $this->user->id,
            'phone_number' => '08123456789',
            'address' => 'Jalan Test No. 123',
            'city' => 'Jakarta',
            'postal_code' => '12345',
            'province' => 'DKI Jakarta',
            'country' => 'ID',
        ]);
    }

    // Test 1: User terautentikasi bisa melakukan checkout
    /** @test */
    public function authenticated_user_can_checkout_their_cart()
    {
        // Tambahkan item ke cart
        PhotobookCart::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'template_id' => $this->template->id,
            'quantity' => 2, // 2 item @ 150000 = 300000
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/checkout');
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'order' => [
                'id',
                'order_number',
                'total_amount',
                'status',
                'items' => [
                    '*' => [
                        'id',
                        'product_id',
                        'template_id',
                        'quantity',
                        'price', // Harga per item
                    ]
                ]
            ],
            // 'payment_url' akan ditambahkan setelah integrasi Midtrans
        ]);

        // Assert order dibuat dengan benar
        $this->assertDatabaseHas('photobook_orders', [
            'user_id' => $this->user->id,
            'status' => 'pending',
            'total_amount' => 300000, // 2 * 150000
            // order_number akan di-generate secara unik
        ]);

        // Assert order item dibuat
        $this->assertDatabaseHas('photobook_order_items', [
            'order_id' => $response->json('order.id'),
            'product_id' => $this->product->id,
            'template_id' => $this->template->id,
            'quantity' => 2,
            'price' => 150000,
        ]);

        // Assert cart dikosongkan
        $this->assertDatabaseMissing('photobook_carts', [
            'user_id' => $this->user->id,
        ]);
    }

    // Test 2: User harus login untuk checkout
    /** @test */
    public function guest_cannot_checkout()
    {
        $response = $this->postJson('/api/checkout');

        $response->assertStatus(401); // Unauthorized
    }

    // Test 3: Tidak bisa checkout cart kosong
    /** @test */
    public function user_cannot_checkout_empty_cart()
    {
        $response = $this->actingAs($this->user)->postJson('/api/checkout');

        // Bisa jadi 400 Bad Request atau 422 Unprocessable Entity
        $response->assertStatus(400);
        $response->assertJson(['error' => 'Your cart is empty']);
    }

    // Test 4: Validasi profil customer (opsional untuk versi awal)
    // Kita bisa tambahkan test ini nanti jika ingin memastikan profil lengkap wajib diisi sebelum checkout.
    // Untuk sekarang, kita asumsikan profil sudah ada dari setUp.

}
