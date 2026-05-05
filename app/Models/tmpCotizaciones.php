<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tmpCotizaciones extends Model
{
    use HasFactory;

    protected $fillable = [
        'producto',
        'name',
        'price',
        'quantity',
        'sucursal',
        'codebar',
        'descuento',
        'total',
        'medida',
        'limit',
        'descargar',
        'uni',
        'pre',
        'usuario',
        'caja',
        'esenario'
    ];
}
