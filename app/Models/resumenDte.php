<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class resumenDte extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected$fillable = [
        'dte',
        'totalNoSuj',
        'totalExenta',
        'totalGravada',
        'totalIva',
        'subTotalVentas',
        'descuNoSuj',
        'descuExenta',
        'descuGravada',
        'porcentajeDescuento',
        'totalDescu',
        'tributo',
        'codigo',
        'descripcion',
        'valor',
        'subTotal',
        'ivaPerci1',
        'ivaRete1',
        'reteRenta',
        'montoTotalOperacion',
        'totalNoGravado',
        'totalPagar',
        'totalLetras',
        'saldoFavor',
        'condicionOperacion',
        'pagos',
        'montoPagado',
        'refencia',
        'palzo',
        'periodo',
        'numPagoElectronico',
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
