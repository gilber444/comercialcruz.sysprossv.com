<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Caja extends Model
{
    use HasFactory;

    protected $fillable = [
        'caja',
        'sucursal',
        'empresa',
        'corte',
        'venta',
        'facturador',
        'tipoPago',
        'correlativo',
        'codigo',
        'numero',
        'sello',
        'fecha',
        'hora',
        'cajero',
        'comprobante',
        'efectivo',
        'cambio',
        'subtotal',
        'descuento',
        'iva',
        'percepcion',
        'total',
        'estado',
        'arqueado',
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

    public function Rventas()
    {
        return $this->belongsTo(Ventas::class, 'venta');
    }

    public function Rcajas()
    {
        return $this->belongsTo(Parametros::class, 'caja');
    }

    public function Rcortes()
    {
        return $this->belongsTo(Cortes::class, 'corte');
    }

    public function Rcajeros()
    {
        return $this->belongsTo(User::class, 'cajero');
    }

    public function Rfacturadores()
    {
        return $this->belongsTo(Facturadores::class, 'facturador');
    }
}
