<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\PhotobookProduct;
use App\Models\PhotobookTemplate;
use App\Models\PhotobookCart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class PhotobookCartTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $product;
    protected $template;

    protected function setUp(): void
    {
        parent::setUp();

        // Membuat user, produk, dan template untuk testing
        $this->user = User::factory()->create();
        $this->product = PhotobookProduct::factory()->create(['is_active' => true]);
        $this->template = PhotobookTemplate::factory()->create([
            'product_id' => $this->product->id,
            'is_active' => true
        ]);
    }

    // Test 1: User bisa menambahkan item ke cart
    /** @test */
    public function authenticated_user_can_add_item_to_cart()
    {
        $response = $this->actingAs($this->user)->postJson('/api/cart', [
            'product_id' => $this->product->id,
            'template_id' => $this->template->id,
            'quantity' => 2,
            'design_same' => true
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'cart_item' => [
                'id',
                'user_id',
                'product_id',
                'template_id',
                'quantity',
                'design_same'
            ]
        ]);

        $this->assertDatabaseHas('photobook_carts', [
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'template_id' => $this->template->id,
            'quantity' => 2,
            'design_same' => true
        ]);
    }

    // Test 2: User harus login untuk menambahkan ke cart
    /** @test */
    public function guest_cannot_add_item_to_cart()
    {
        $response = $this->postJson('/api/cart', [
            'product_id' => $this->product->id,
            'template_id' => $this->template->id,
            'quantity' => 1,
            'design_same' => false
        ]);

        $response->assertStatus(401); // Unauthorized
    }

    // Test 3: Validasi input diperlukan
    /** @test */
    public function adding_item_to_cart_requires_valid_data()
    {
        $response = $this->actingAs($this->user)->postJson('/api/cart', [
            // Tidak ada data
        ]);

        $response->assertStatus(422); // Unprocessable Entity
        $response->assertJsonValidationErrors(['product_id', 'quantity']);
    }

    // Test 4: Produk harus aktif
    /** @test */
    public function cannot_add_inactive_product_to_cart()
    {
        $inactiveProduct = PhotobookProduct::factory()->inactive()->create();
        $inactiveTemplate = PhotobookTemplate::factory()->create([
            'product_id' => $inactiveProduct->id
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/cart', [
            'product_id' => $inactiveProduct->id,
            'template_id' => $inactiveTemplate->id,
            'quantity' => 1,
            'design_same' => false
        ]);

        $response->assertStatus(422);
        // Bisa tambahkan pengecekan pesan error spesifik jika diinginkan
    }

    // Test 5: User bisa melihat isi cart mereka
    /** @test */
    public function authenticated_user_can_view_their_cart()
    {
        // Tambahkan beberapa item ke cart
        PhotobookCart::factory()->count(2)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->getJson('/api/cart');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data'); // Memastikan ada 2 item
        $response->assertJsonStructure([
            'data' => [
                '*' => [ // '*' artinya setiap item dalam array
                    'id',
                    'product' => ['id', 'name'],
                    'template' => ['id', 'name'],
                    'quantity',
                    'design_same'
                ]
            ]
        ]);
    }

    // Test 6: Guest tidak bisa melihat cart
    /** @test */
    public function guest_cannot_view_cart()
    {
        $response = $this->getJson('/api/cart');

        $response->assertStatus(401);
    }

    // Test 7: User bisa menghapus item dari cart
    /** @test */
    public function authenticated_user_can_remove_item_from_cart()
    {
        $cartItem = PhotobookCart::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->deleteJson("/api/cart/{$cartItem->id}");

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Item removed from cart']);

        $this->assertDatabaseMissing('photobook_carts', [
            'id' => $cartItem->id
        ]);
    }

    // Test 8: User hanya bisa menghapus item milik mereka sendiri
    /** @test */
    public function user_cannot_remove_another_users_cart_item()
    {
        $otherUser = User::factory()->create();
        $otherUserCartItem = PhotobookCart::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)->deleteJson("/api/cart/{$otherUserCartItem->id}");

        // Bisa jadi 403 Forbidden atau 404 Not Found, tergantung implementasi
        $response->assertStatus(403); // Misalnya, kita pilih 403
    }
}