<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;


class Clientes extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['nombreCliente', 'tipoPersona', 'dui', 'nit', 'homologado', 'registro', 'giro',
    'departamento', 'municipio', 'distrito', 'email', 'celular', 'actividad', 'direccion', 'telefono', 'idenReceptor', 'homologado', 'desActividad','sincro_id'];

    protected $hidden = ['dui', 'nit', 'email', 'celular', 'direccion', 'telefono'];

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
