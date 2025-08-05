<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

        protected $fillable = [
        'code',
        'description',
        'discount_percent',
        // 'discount_amount', // Jika menggunakan diskon nominal
        'max_uses',
        'max_uses_per_user',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'discount_percent' => 'decimal:2', // Cast ke decimal dengan 2 desimal
        // 'discount_amount' => 'decimal:2', // Jika menggunakan diskon nominal
    ];

    /**
     * Scope a query to only include active coupons.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include coupons that are currently valid based on date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
        })->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Cek apakah kupon ini masih bisa digunakan berdasarkan batasan total.
     *
     * @return bool
     */
    public function isUsable(): bool
    {
        // Cek status aktif
        if (!$this->is_active) {
            return false;
        }

        // Cek tanggal validitas
        $now = now();
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false; // Belum mulai
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false; // Sudah kadaluarsa
        }

        // Cek batasan penggunaan total
        if ($this->max_uses !== null && $this->times_used >= $this->max_uses) {
            return false; // Sudah mencapai batas maksimal penggunaan
        }

        return true;
    }

        public function orders()
    {
        // Gantilah 'photobook_orders' dan 'order_id' jika nama tabel/kolom berbeda
        return $this->belongsToMany(PhotobookOrder::class, 'coupon_order', 'coupon_id', 'order_id');
    }
    // Tambahkan relasi jika nanti ada tabel untuk melacak penggunaan kupon per user/order
    // Misalnya:
    // public function usages()
    // {
    //     return $this->hasMany(CouponUsage::class); // Anda perlu membuat model CouponUsage
    // }
    //
    // public function orders()
    // {
    //     return $this->belongsToMany(PhotobookOrder::class, 'coupon_order'); // Pivot table coupon_order
    // }
}
