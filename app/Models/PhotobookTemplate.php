<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhotobookTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'layout_data',
        'sample_image',
        'is_active',
    ];

    protected $casts = [
        'layout_data' => 'array',
        'is_active' => 'boolean',
    ];

    // Relasi
    public function product()
    {
        return $this->belongsTo(PhotobookProduct::class);
    }

    public function orderItems()
    {
        return $this->hasMany(PhotobookOrderItem::class);
    }
}