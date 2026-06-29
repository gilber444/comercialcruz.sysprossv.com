<?php

namespace App\Models;

use App\Models\Facturadores;
use App\Models\TipoDocumento;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class Ventas extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente',
        'tipoPago',
        'facturador',
        'correlativo',
        'fecha',
        'hora',
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
        'qr',
        'codigoVendedor',
        'costociva',
        'totalcosto',
        'sincro_id', // opcionalmente lo agregas al fillable

    ];

    protected $hidden = ['costociva', 'totalcosto'];

    // Genera sincro_id si no viene seteado
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->sincro_id)) {
                $model->sincro_id = (string) Str::uuid();
            }
        });
    }

    public function Rfacturadors()
    {
        return $this->belongsTo(Facturadores::class, 'facturador');
    }

    public function facturadors()
    {
        return $this->belongsTo(Facturadores::class, 'facturador');
    }

    public function RdetalleVentas()
    {
        return $this->hasMany(VentasDetalles::class, 'venta');
    }

    public function Rvendedor()
    {
        return $this->belongsTo(User::class, 'vendedor');
    }

    public function Rcaja()
    {
        return $this->belongsTo(Parametros::class, 'caja');
    }

    public function Rcajas()
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

    public function Rclientes()
    {
        return $this->belongsTo(Clientes::class, 'cliente');
    }

    public function Rcliente()
    {
        return $this->belongsTo(Clientes::class, 'cliente');
    }

    public function RcortesCaja()
    {
        return $this->hasMany(Cortes::class, 'corte');
    }
}
