<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\PhotobookCustomerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PhotobookCustomerProfileTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_customer_profile_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $profile = PhotobookCustomerProfile::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $profile->user);
        $this->assertEquals($user->id, $profile->user->id);
    }

    /** @test */
    public function a_customer_profile_has_correct_address_info()
    {
        $profile = PhotobookCustomerProfile::factory()->create([
            'phone_number' => '08123456789',
            'address' => 'Jalan Merdeka No. 123',
            'city' => 'Jakarta',
            'postal_code' => '12345',
            'province' => 'DKI Jakarta',
            'country' => 'ID'
        ]);

        $this->assertEquals('08123456789', $profile->phone_number);
        $this->assertEquals('Jalan Merdeka No. 123', $profile->address);
        $this->assertEquals('Jakarta', $profile->city);
        $this->assertEquals('12345', $profile->postal_code);
        $this->assertEquals('DKI Jakarta', $profile->province);
        $this->assertEquals('ID', $profile->country);
    }
}