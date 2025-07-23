<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\PhotobookProduct;
use App\Models\PhotobookTemplate;
use App\Models\PhotobookOrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PhotobookProductTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_product_can_have_multiple_templates()
    {
        $product = PhotobookProduct::factory()->create();
        PhotobookTemplate::factory()->count(3)->create(['product_id' => $product->id]);

        $this->assertEquals(3, $product->templates->count());
    }

    /** @test */
    public function a_product_can_be_active_or_inactive()
    {
        $activeProduct = PhotobookProduct::factory()->create(['is_active' => true]);
        $inactiveProduct = PhotobookProduct::factory()->inactive()->create();

        $this->assertTrue($activeProduct->is_active);
        $this->assertFalse($inactiveProduct->is_active);
    }

    /** @test */
    public function a_product_can_have_many_order_items()
    {
        $product = PhotobookProduct::factory()->create();
        PhotobookOrderItem::factory()->count(2)->create(['product_id' => $product->id]);

        $this->assertEquals(2, $product->orderItems->count());
    }
}