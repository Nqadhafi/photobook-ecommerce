<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhotobookOrderFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'order_item_id',
        'file_path',
        'status',
        'notes',
        'uploaded_at',
        'confirmed_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    // Relasi
    public function order()
    {
        return $this->belongsTo(PhotobookOrder::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(PhotobookOrderItem::class);
    }
}