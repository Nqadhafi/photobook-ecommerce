<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\PhotobookOrder;
use App\Models\PhotobookOrderItem;
use App\Models\PhotobookOrderFile;
use App\Models\PhotobookMidtransPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PhotobookOrderTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_order_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $order = PhotobookOrder::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $order->user);
        $this->assertEquals($user->id, $order->user->id);
    }

    /** @test */
    public function an_order_can_have_multiple_items()
    {
        $order = PhotobookOrder::factory()->create();
        PhotobookOrderItem::factory()->count(3)->create(['order_id' => $order->id]);

        $this->assertEquals(3, $order->items->count());
    }

    /** @test */
    public function an_order_can_have_multiple_files()
    {
        $order = PhotobookOrder::factory()->create();
        PhotobookOrderFile::factory()->count(2)->create(['order_id' => $order->id]);

        $this->assertEquals(2, $order->files->count());
    }

    /** @test */
    public function an_order_has_one_payment()
    {
        $order = PhotobookOrder::factory()->create();
        $payment = PhotobookMidtransPayment::factory()->create(['order_id' => $order->id]);

        $this->assertInstanceOf(PhotobookMidtransPayment::class, $order->payment);
        $this->assertEquals($payment->id, $order->payment->id);
    }

    /** @test */
    public function order_status_changes_correctly()
    {
        $order = PhotobookOrder::factory()->create(['status' => 'pending']);
        
        $this->assertEquals('pending', $order->status);
        
        $order->update(['status' => 'paid', 'paid_at' => now()]);
        $this->assertEquals('paid', $order->status);
        $this->assertNotNull($order->paid_at);
    }

    /** @test */
    public function order_can_be_scoped_by_status()
    {
        PhotobookOrder::factory()->paid()->create();
        PhotobookOrder::factory()->processing()->create();
        PhotobookOrder::factory()->completed()->create();

        $this->assertEquals(1, PhotobookOrder::paid()->count());
        $this->assertEquals(1, PhotobookOrder::processing()->count());
        $this->assertEquals(1, PhotobookOrder::completed()->count());
    }
}