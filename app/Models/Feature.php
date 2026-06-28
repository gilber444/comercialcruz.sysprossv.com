<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    use HasFactory;

    protected $fillable = [
        'version',
        'descripcion',
        'activo',
        'produccion',
    ];

    protected $casts = [
        'activo'     => 'boolean',
        'produccion' => 'boolean',
    ];
}
