<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tmpHojaInventario extends Model
{
    use HasFactory;

    protected $fillable = [
        'producto', 'hoja', 'sucursal', 'name', 'codebar', 'medida', 'existencia', 'cantidad', 'conteoFisico', 'diferencia', 'limit', 'costo', 'total', 'user'
    ];
}
