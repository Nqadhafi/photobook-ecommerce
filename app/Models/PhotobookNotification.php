<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhotobookNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'title',
        'message',
        'is_read',
        'read_at',
        'action_url',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    // Relasi
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(PhotobookOrder::class);
    }

    // Scope
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
}