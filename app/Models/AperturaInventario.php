<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;

class AperturaInventario extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'aperturas_inventario';

    protected $fillable = [
        'empresa',
        'sucursal',
        'fecha_apertura',
        'hora_apertura',
        'fecha_cierre',
        'hora_cierre',
        'responsable',
        'user',
        'estado',
        'observacion',
        'total_sistema',
        'total_fisico',
        'total_diferencia',
        'sincro_id'
    ];

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

    public function Rusuarios()
    {
        return $this->belongsTo(User::class, 'user');
    }

    public function Rhojas()
    {
        return $this->hasMany(HojaInventario::class, 'apertura_id');
    }
}
