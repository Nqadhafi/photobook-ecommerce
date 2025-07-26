<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhotobookOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'template_id',
        'quantity',
        'price',
        'design_same',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'design_same' => 'boolean',
    ];

    // Relasi
    public function order()
    {
        return $this->belongsTo(PhotobookOrder::class , 'order_id');
    }

    public function product()
    {
        return $this->belongsTo(PhotobookProduct::class , 'product_id');
    }

    public function template()
    {
        return $this->belongsTo(PhotobookTemplate::class , 'template_id');
    }

    public function files()
    {
        return $this->hasMany(PhotobookOrderFile::class, 'order_item_id');
    }
}