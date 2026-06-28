<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tmpSolicitudes extends Model
{
    use HasFactory;

    protected $fillable = [
        'codebar',
        'producto',
        'name',
        'cantidad',
        'costo',
        'total',
        'unidad',
        'medida',
        'limit',
        'descargar',
        'user'
    ];
}
