<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePhotobookCustomerProfilesTable extends Migration
{
    public function up()
    {
        Schema::create('photobook_customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('phone_number')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('province')->nullable();
            $table->string('country')->default('ID');
            $table->timestamps();
            
            $table->unique('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('photobook_customer_profiles');
    }
}
