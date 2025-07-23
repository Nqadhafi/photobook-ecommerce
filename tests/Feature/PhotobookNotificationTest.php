<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\PhotobookOrder;
use App\Models\PhotobookNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PhotobookNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $order;

    protected function setUp(): void
    {
        parent::setUp();

        // Membuat user dan order untuk testing
        $this->user = User::factory()->create();
        $this->order = PhotobookOrder::factory()->create([
            'user_id' => $this->user->id,
        ]);
    }

    // Test 1: User bisa melihat notifikasi mereka
    /** @test */
    public function authenticated_user_can_fetch_their_notifications()
    {
        // Buat beberapa notifikasi untuk user
        PhotobookNotification::factory()->count(3)->create(['user_id' => $this->user->id]);
        // Buat notifikasi untuk user lain untuk memastikan tidak tampil
        PhotobookNotification::factory()->create();

        $response = $this->actingAs($this->user)->getJson('/api/notifications');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data'); // Hanya notifikasi milik user ini
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'message',
                    'is_read',
                    'created_at',
                    // 'order' => ['id', 'order_number'] // Jika eager load order
                ]
            ]
        ]);
    }

    // Test 2: Guest tidak bisa mengambil notifikasi
    /** @test */
    public function guest_cannot_fetch_notifications()
    {
        $response = $this->getJson('/api/notifications');

        $response->assertStatus(401); // Unauthorized
    }

    // Test 3: User bisa menandai notifikasi sebagai dibaca
    /** @test */
    public function authenticated_user_can_mark_notification_as_read()
    {
        $notification = PhotobookNotification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => false
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Notification marked as read']);

        // Assert database
        $this->assertDatabaseHas('photobook_notifications', [
            'id' => $notification->id,
            'is_read' => true,
            // 'read_at' => // Bisa dicek jika ingin memastikan timestamp diisi
        ]);
    }

    // Test 4: User tidak bisa menandai notifikasi orang lain sebagai dibaca
    /** @test */
    public function user_cannot_mark_another_users_notification_as_read()
    {
        $otherUser = User::factory()->create();
        $otherUserNotification = PhotobookNotification::factory()->create([
            'user_id' => $otherUser->id,
            'is_read' => false
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/notifications/{$otherUserNotification->id}/read");

        // Bisa jadi 403 Forbidden atau 404 Not Found, tergantung implementasi
        // Kita pilih 403 Forbidden untuk kasus ini
        $response->assertStatus(403);
    }

    // Test 5: Guest tidak bisa menandai notifikasi sebagai dibaca
    /** @test */
    public function guest_cannot_mark_notification_as_read()
    {
        $notification = PhotobookNotification::factory()->create(['is_read' => false]);

        $response = $this->putJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(401); // Unauthorized
    }
}
