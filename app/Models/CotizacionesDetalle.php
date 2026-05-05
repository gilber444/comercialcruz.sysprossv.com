<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class CotizacionesDetalle extends Model
{
    use HasFactory;
    protected $fillable = [
        'cotizacion',
        'producto',
        'medida',
        'unidad',
        'descargar',
        'cantidad',
        'precio',
        'descuento',
        'subtotal',
        'iva',
        'total',
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

    public function cotizacions()
    {
        return $this->belongsTo(Cotizaciones::class, 'cotizacion');
    }

    public function Rproductos()
    {
        return $this->belongsTo(Productos::class, 'producto');
    }

    protected $casts = [
        'cantidad' => 'integer',
        'precio' => 'decimal:4',
        'descuento' => 'decimal:4',
        'subtotal' => 'decimal:4',
    ];
}
