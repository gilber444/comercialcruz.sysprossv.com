<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;


class Cotizaciones extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'cliente',
        'tipoPago',
        'facturador',
        'tipoDocumento',
        'correlativo',
        'fecha',
        'hora',
        'fechaPago',
        'tipo',
        'codigo',
        'numero',
        'sello',
        'vendedor',
        'caja',
        'sucursal',
        'empresa',
        'subtotal',
        'descuento',
        'iva',
        'percepcion',
        'total',
        'estado',
        'observaciones',
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

    // En el modelo Cotizaciones
    public function Rdetalles()
    {
        return $this->hasMany(CotizacionesDetalle::class, 'cotizacion');
    }

    public function Rcliente()
    {
        return $this->belongsTo(Clientes::class, 'cliente');
    }

    public function RtipoPago()
    {
        return $this->belongsTo(TipoPagos::class, 'tipoPago');
    }

    public function Rfacturador()
    {
        return $this->belongsTo(Facturadores::class, 'facturador');
    }

    public function RtipoDocumentos()
    {
        return $this->belongsTo(TipoDocumento::class, 'tipoDocumento');
    }

    public function Rvendedor()
    {
        return $this->belongsTo(User::class, 'vendedor');
    }

    public function Rcaja()
    {
        return $this->belongsTo(Parametros::class, 'caja');
    }

    public function Rsucursal()
    {
        return $this->belongsTo(Sucursales::class, 'sucursal');
    }

    public function Rempresa()
    {
        return $this->belongsTo(Empresas::class, 'empresa');
    }

    // Definir casts para convertir atributos a tipos nativos de PHP
    protected $casts = [
        'fecha' => 'date',
        'hora' => 'datetime:H:i:s',
        'fechaPago' => 'date',
        'subtotal' => 'decimal:4',
        'descuento' => 'decimal:4',
        'iva' => 'decimal:4',
        'percepcion' => 'decimal:4',
        'total' => 'decimal:4',
    ];
}
