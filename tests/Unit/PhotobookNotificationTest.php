<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\PhotobookOrder;
use App\Models\PhotobookNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PhotobookNotificationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_notification_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $notification = PhotobookNotification::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $notification->user);
        $this->assertEquals($user->id, $notification->user->id);
    }

    /** @test */
    public function a_notification_can_belong_to_an_order()
    {
        $order = PhotobookOrder::factory()->create();
        $notification = PhotobookNotification::factory()->create(['order_id' => $order->id]);

        $this->assertInstanceOf(PhotobookOrder::class, $notification->order);
        $this->assertEquals($order->id, $notification->order->id);
    }

    /** @test */
    public function a_notification_can_be_read_or_unread()
    {
        $unreadNotification = PhotobookNotification::factory()->create(['is_read' => false]);
        $readNotification = PhotobookNotification::factory()->read()->create();

        $this->assertFalse($unreadNotification->is_read);
        $this->assertTrue($readNotification->is_read);
        $this->assertNotNull($readNotification->read_at);
    }

    /** @test */
    public function notifications_can_be_scoped_by_read_status()
    {
        PhotobookNotification::factory()->count(3)->create(['is_read' => false]);
        PhotobookNotification::factory()->count(2)->read()->create();

        $this->assertEquals(3, PhotobookNotification::unread()->count());
        $this->assertEquals(2, PhotobookNotification::where('is_read', true)->count());
    }
}