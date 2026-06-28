<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Compras extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'numero',
        'tipo',
        'correlativo',
        'serie',
        'fecha',
        'condi_pago',
        'vendedor',
        'estado',
        'fechaPAgo',
        'proveedor',
        'user',
        //'subtotal',
        //'iva',
        //'percepcion',
        //'total',
        'sucursal',
        'sincro_id',
        'generacion'
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

    public function Rsucursal()
    {
        return $this->belongsTo(Sucursales::class, 'sucursal');
    }

    public function Rusers()
    {
        return $this->belongsTo(User::class, 'user');
    }

    public function Rproveedores()
    {
        return $this->belongsTo(Proveedores::class, 'proveedor');
    }

    public function RtipoCompra()
    {
        return $this->belongsTo(tipoCompras::class, 'tipo');
    }


}
