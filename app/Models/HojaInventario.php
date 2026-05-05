<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;


class HojaInventario extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'apertura_id',
        'correlativo',
        'fecha',
        'hora',
        'fecha_inicio',
        'hora_inicio',
        'fecha_fin',
        'hora_fin',
        'responsable',
        'user',
        'empresa',
        'sucursal',
        'estado',
        'sincro_id'
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

    public function Rapertura()
    {
        return $this->belongsTo(AperturaInventario::class, 'apertura_id');
    }

    public function Rusuarios()
    {
        return $this->belongsTo(User::class, 'user');
    }

    public function Rempresas()
    {
        return $this->belongsTo(Empresas::class, 'empresa');
    }

    public function Rsucursales()
    {
        return $this->belongsTo(Sucursales::class, 'sucursal');
    }

    public function Rdetalles()
    {
        return $this->hasMany(HojaInventarioDetalles::class, 'hoja');
    }
}
