<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tmpSolicitud extends Model
{
    use HasFactory;

    protected $fillable = [
        'producto',
        'inventario',
        'sucursal',
        'medida',
        'codebar',
        'name',
        'unidad',
        'cantidad',
        'contenedor',
        'solicita',
        'costo',
        'usuario'
    ];
}
