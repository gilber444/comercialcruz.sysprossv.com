<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tmpAjuste extends Model
{
    use HasFactory;

    protected $fillable = [
        'producto',
        'name',
        'price',
        'quantity',
        'sucursal',
        'codebar',
        'unidad',
        'total',
        'limit',
        'ingreso',
        'inventario',
        'usuario'
    ];
}
