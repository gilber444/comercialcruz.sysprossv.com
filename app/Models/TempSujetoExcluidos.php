<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempSujetoExcluidos extends Model
{ 
    use HasFactory;

    protected $table = 'temp_sujeto_excluidos';
   protected $fillable = [

        'user_id',
        'empresa',
        'sucursal',
        'producto',
        'unidad',
        'name',
        'cantidad' ,
        'costo' ,
        'toatal' 
    ];
}

