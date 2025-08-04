<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGoogleDriveFolderUrlToPhotobookOrdersTable extends Migration
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
            $table->string('google_drive_folder_url')->nullable()->after('cancelled_at');
            $table->string('google_drive_folder_id')->nullable()->after('google_drive_folder_url');
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
            $table->dropColumn(['google_drive_folder_url']);
            $table->dropColumn(['google_drive_folder_id']);
        });
    }
}
