<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tmpVentas extends Model
{
    use HasFactory;

    protected $fillable =[
        'producto',
        'pid',
        'familia',
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
        'user',
        'caja',
        'empresa',
        'esenario',
        'costo',
        'costo_total',
        'utilidad_uni',
        'utilidad'
    ];
}
