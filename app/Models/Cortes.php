<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;


class Cortes extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable =[
        'caja',
        'sucursal',
        'empresa',
        'corte',
        'fecha',
        'hora',
        'estado',
        'efectivo',
        'tarjeta',
        'cheque',
        'credito',
        'transferencia',
        'subtotalPagos',
        'devoluciones',
        'anulaciones',
        'remesas',
        'otrosIngresos',
        'percepcion',
        'cortes',
        'sumaTotales',
        'ticketDesde',
        'ticketHasta',
        'gravadosT',
        'ivaT',
        'subT',
        'totalT',
        'consumidorDesde',
        'consumidorHasta',
        'gravadosCon',
        'ivaCon',
        'subCon',
        'totalCon',
        'CreDesde',
        'CreHasta',
        'gravadosCre',
        'ivaCre',
        'subCre',
        'totalCre',
        'dteDesde',
        'dteHasta',
        'gravadosDTE',
        'ivaDTE',
        'subDTE',
        'totalDTE',
        'creditosDesde',
        'creditosHasta',
        'gravadosCredi',
        'ivaCredi',
        'subCredi',
        'totalCredi',
        'totalGeneral',
        'ivaGeneral',
        'subGeneral',
        'totalPercepcion',
        'totalGlobal',
        'totalEfectivo',
        'diferencia',
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

    public function Rparametros()
    {
        return $this->belongsTo(Parametros::class, 'caja');
    }

    public function Rsucursal()
    {
        return$this->belongsTo(Sucursales::class, 'sucursal');
    }
}
