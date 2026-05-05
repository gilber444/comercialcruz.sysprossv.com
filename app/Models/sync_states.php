<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class sync_states extends Model
{
    use HasFactory;

    protected $fillable = [
        'direction',
        'table',
        'watermark_updated_at',
        'watermark_id',
        'meta',
    ];

    protected $casts = [
        'watermark_updated_at' => 'datetime',
        'meta' => 'array',
    ];
}
