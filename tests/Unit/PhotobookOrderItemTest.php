<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\PhotobookOrder;
use App\Models\PhotobookProduct;
use App\Models\PhotobookTemplate;
use App\Models\PhotobookOrderItem;
use App\Models\PhotobookOrderFile;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PhotobookOrderItemTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_order_item_belongs_to_an_order()
    {
        $order = PhotobookOrder::factory()->create();
        $item = PhotobookOrderItem::factory()->create(['order_id' => $order->id]);

        $this->assertInstanceOf(PhotobookOrder::class, $item->order);
        $this->assertEquals($order->id, $item->order->id);
    }

    /** @test */
    public function an_order_item_belongs_to_a_product()
    {
        $product = PhotobookProduct::factory()->create();
        $item = PhotobookOrderItem::factory()->create(['product_id' => $product->id]);

        $this->assertInstanceOf(PhotobookProduct::class, $item->product);
        $this->assertEquals($product->id, $item->product->id);
    }

    /** @test */
    public function an_order_item_can_belong_to_a_template()
    {
        $template = PhotobookTemplate::factory()->create();
        $item = PhotobookOrderItem::factory()->create(['template_id' => $template->id]);

        $this->assertInstanceOf(PhotobookTemplate::class, $item->template);
        $this->assertEquals($template->id, $item->template->id);
    }

    /** @test */
    public function an_order_item_can_have_multiple_files()
    {
        $item = PhotobookOrderItem::factory()->create();
        PhotobookOrderFile::factory()->count(2)->create(['order_item_id' => $item->id]);

        $this->assertEquals(2, $item->files->count());
    }

    /** @test */
    public function order_item_has_correct_price_and_quantity()
    {
        $item = PhotobookOrderItem::factory()->create([
            'quantity' => 3,
            'price' => 150000
        ]);

        $this->assertEquals(3, $item->quantity);
        $this->assertEquals(150000, $item->price);
        $this->assertEquals(450000, $item->quantity * $item->price); // 3 * 150000
    }
}