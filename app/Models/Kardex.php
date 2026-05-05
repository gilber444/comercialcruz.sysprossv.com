<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class Kardex extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'producto',
        'inventario',
        'descripcion',
        'fecha',
        'hora',
        'ingresoCantidad',
        'ingresoValor',
        'egresoCantidad',
        'egresoValor',
        'saldoCantidad',
        'saldoValor',
        'sucursal',
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

    public function Rproductos()
    {
        return $this->belongsTo(Productos::class, 'producto', 'id');
    }

    public function Rsucursales()
    {
        return $this->belongsTo(Sucursales::class, 'sucursal', 'id');
    }

    public function Rinventarios()
    {
        return $this->belongsTo(Inventarios::class, 'inventario', 'id');
    }

    public function Rinventario()
    {
        return $this->belongsTo(Inventarios::class, 'inventario');
    }
}
