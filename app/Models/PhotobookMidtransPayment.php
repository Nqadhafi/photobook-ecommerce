<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhotobookMidtransPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'snap_token',
        'redirect_url',
        'transaction_id',
        'fraud_status',
        'transaction_status',
        'payment_type',
        'gross_amount',
        'bank',
        'va_numbers',
        'bill_key',
        'biller_code',
        'payment_code',
        'status_code',
        'status_message',
        'merchant_id',
        'signature_key',
        'payload',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'customer_city',
        'paid_at',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'va_numbers' => 'array',
        'payload' => 'array',
        'paid_at' => 'datetime',
    ];

    // Relasi
    public function order()
    {
        return $this->belongsTo(PhotobookOrder::class);
    }
}