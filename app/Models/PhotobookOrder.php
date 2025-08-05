<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhotobookOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_number',
        'total_amount',
        'status',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'customer_city',
        'customer_postal_code',
        'pickup_code',
        'paid_at',
        'file_uploaded_at',
        'processing_at',
        'ready_at',
        'picked_up_at',
        'completed_at',
        'cancelled_at',
        'google_drive_folder_url',
        'google_drive_folder_id',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'file_uploaded_at' => 'datetime',
        'processing_at' => 'datetime',
        'ready_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

        public function coupons()
    {
        // Gantilah 'photobook_orders' dan 'order_id' jika nama tabel/kolom berbeda
        return $this->belongsToMany(Coupon::class, 'coupon_order', 'order_id', 'coupon_id');
    }

    // Jika satu order hanya bisa pakai satu kupon, gunakan belongsTo dan tambah kolom coupon_id di tabel photobook_orders
    // public function coupon()
    // {
    //     return $this->belongsTo(Coupon::class);
    // }

    // Relasi
    public function user()
    {
        return $this->belongsTo(User::class , 'user_id');
    }

    public function items()
    {
        return $this->hasMany(PhotobookOrderItem::class,'order_id');
    }

    public function files()
    {
        return $this->hasMany(PhotobookOrderFile::class, 'order_id');
    }

    public function payment()
    {
        return $this->hasOne(PhotobookMidtransPayment::class, 'order_id');
    }

    public function notifications()
    {
        return $this->hasMany(PhotobookNotification::class, 'order_id');
    }

    // Scope
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeFileUpload($query)
    {
        return $query->where('status', 'file_upload');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeReady($query)
    {
        return $query->where('status', 'ready');
    }
    public function scopeCompleted($query)
{
    return $query->where('status', 'completed');
}

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }
}