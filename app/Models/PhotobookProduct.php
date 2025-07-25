<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhotobookProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'thumbnail',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relasi
    public function templates()
    {
        return $this->hasMany(PhotobookTemplate::class, 'product_id');
    }

    public function orderItems()
    {
        return $this->hasMany(PhotobookOrderItem::class, 'product_id');
    }
}