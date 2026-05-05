<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class envioTmp extends Model
{
    use HasFactory;

    protected $fillable = ['usuario', 'empresa', 'sucursal', 'caja', 'envio'];
}
