<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Devoluciones extends Model
{
    use HasFactory;

    protected $fillable = [
        'caja',
        'sucursal',
        'empresa',
        'corte',
        'venta',
        'cajas',
        'facturador',
        'tipoPago',
        'correlativo',
        'codigo',
        'numero',
        'sello',
        'fecha',
        'hora',
        'cajero',
        'autorizado',
        'comprobante',
        'efectivo',
        'cambio',
        'subtotal',
        'descuento',
        'iva',
        'total',
        'estado',
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
}
