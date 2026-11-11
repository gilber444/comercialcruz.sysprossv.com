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
        'generacion',

        // Anexo 3 del F07 (migracion 2026_08_08_120000). Sin estos nombres
        // aqui Eloquent los descarta EN SILENCIO al hacer create()/update().
        'empresa',
        'clase_documento',
        'tipo_documento',
        'numero_resolucion',
        'codigo_generacion',
        'sello_recibido',
        'compras_exentas',
        'internaciones_exentas',
        'importaciones_exentas',
        'compras_gravadas',
        'internaciones_gravadas',
        'importaciones_gravadas',
        'importaciones_servicios',
        'credito_fiscal',
        'total_compra',
        'renta_tipo_operacion',
        'renta_clasificacion',
        'renta_sector',
        'renta_tipo_costo_gasto',
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
