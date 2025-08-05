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
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin']);
    }

        public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

        public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

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