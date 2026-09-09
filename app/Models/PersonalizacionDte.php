<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalizacionDte extends Model
{
    protected $table = 'personalizaciones_dte';

    protected $fillable = [
        'empresa_id',
        'color_primario',
        'color_encabezado',
        'color_texto',
        'color_texto_suave',
        'color_borde',
        'color_exito',
        'color_peligro',
        'color_fondo',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresas::class, 'empresa_id');
    }
}
