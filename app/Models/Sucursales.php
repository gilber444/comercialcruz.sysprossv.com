<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class Sucursales extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero',
        'nombre',
        'direccion',
        'telefono',
        'cajas',
        'empresa',
        'tipo',
        'departamento',
        'municipio',
        'distrito',
        'sincro_id',
        'modo'
    ];

    // Genera sincro_id si no viene seteado
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->sincro_id)) {
                $model->sincro_id = (string) Str::uuid();
            }
        });
    }

    public function parametros()
    {
        return $this->hasMany(Parametros::class);
    }

    public function tipoEstable()
    {
        return$this->belongsTo(TipoEstablecimiento::class, 'tipo');
    }

    public function Rempresa()
    {
        return $this->belongsTo(Empresas::class, 'empresa');
    }
}
