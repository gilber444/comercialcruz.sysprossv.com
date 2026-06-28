<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class NotaCredito extends Model
{
    use HasFactory, SoftDeletes;

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
        'envio',
        'venta' 
    ];


    public function Rfacturadors()
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

    public function Rventas()
    {
        return $this->belongsTo(Empresas::class, 'venta');
    }
}
