<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhotobookCart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'template_id',
        'quantity',
        'design_same',
        'session_id',
    ];

    protected $casts = [
        'design_same' => 'boolean',
    ];

    // Relasi
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function product()
    {
        return $this->belongsTo(PhotobookProduct::class , 'product_id');
    }

    public function template()
    {
        return $this->belongsTo(PhotobookTemplate::class , 'template_id');
    }
}