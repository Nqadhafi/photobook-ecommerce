<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSubtotalAndDiscountToPhotobookOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('photobook_orders', function (Blueprint $table) {
            //
            $table->decimal('sub_total_amount', 10, 2)->default(0)->after('total_amount');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('sub_total_amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('photobook_orders', function (Blueprint $table) {
            //
            $table->dropColumn('sub_total_amount');
            $table->dropColumn('discount_amount');
        });
    }
}
