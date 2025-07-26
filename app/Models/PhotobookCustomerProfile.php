<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhotobookCustomerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'phone_number',
        'address',
        'city',
        'postal_code',
        'province',
        'country',
    ];

    // Relasi
    public function user()
    {
        return $this->belongsTo(User::class , 'user_id');
    }
}