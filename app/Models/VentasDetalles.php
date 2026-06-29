<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class VentasDetalles extends Model
{
    use HasFactory;

    protected $fillable = [
        'venta',
        'producto',
        'medida',
        'name',
        'unidad',
        'descargar',
        'cantidad',
        'precio',
        'descuento',
        'subtotal',
        'iva',
        'total',
        'costo',
        'costo_total',
        'utilidad_uni',
        'utilidad',
        'sincro_id', // opcionalmente lo agregas al fillable


    ];

    protected $hidden = ['costo', 'costo_total', 'utilidad_uni', 'utilidad'];

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
        return $this->belongsTo(Productos::class, 'producto');
    }

    public function Rventas()
    {
        return $this->belongsTo(Ventas::class, 'venta');
    }

    public function Rmedidas()
    {
        return $this->belongsTo(Medidas::class, 'medida');
    }
}
