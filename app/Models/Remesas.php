<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class Remesas extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa',
        'sucursal',
        'caja',
        'cajero',
        'numero',
        'fecha',
        'hora',
        'monto',
        'validador',
        'estado',
        'arqueado',
        'concepto',
        'sincro_id', // opcionalmente lo agregas al fillable

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

    public function Rempresas()
    {
        return $this->belongsTo(Empresas::class, 'empresa');
    }

    public function Rsucursales()
    {
        return $this->belongsTo(Sucursales::class, 'sucursal');
    }

    public function Rcajas()
    {
        return $this->belongsTo(Parametros::class, 'caja');
    }

    public function Rcajeros()
    {
        return $this->belongsTo(User::class, 'cajero');
    }
}
