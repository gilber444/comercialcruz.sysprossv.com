<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Proveedores extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable =
    [
        'nombre',
        'tipoPersona',
        'direccion',
        'telefono',
        'correo',
        'registro',
        'nit',
        'departamento',
        'municipio',
        'distrito',
        'actividad',
        'desActividad',
        'giro',
        'categoria',
        'sincro_id',
    ];

    protected $hidden = ['nit', 'direccion', 'telefono', 'correo'];

    // Genera sincro_id si no viene seteado
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->sincro_id)) {
                $model->sincro_id = (string) Str::uuid();
            }
        });
    }
}
