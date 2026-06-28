<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tmpCompras extends Model
{
    use HasFactory;

    protected $fillable = [
        'producto',
        'name',
        'price',
        'quantity',
        'sucursal',
        'codebar',
        'vencimiento',
        'total',
        'medida',
        'ingreso',
        'newcosto',
        'idpre',
        'usuario'
    ];
}
