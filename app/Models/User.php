<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Relasi
    public function photobookProfile()
    {
        return $this->hasOne(PhotobookCustomerProfile::class , 'user_id');
    }

    public function photobookOrders()
    {
        return $this->hasMany(PhotobookOrder::class, 'user_id');
    }

    public function photobookNotifications()
    {
        return $this->hasMany(PhotobookNotification::class , 'user_id');
    }

    public function photobookCarts()
    {
        return $this->hasMany(PhotobookCart::class, 'user_id');
    }
}