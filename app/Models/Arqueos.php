<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Arqueos extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'numero',
        'fecha',
        'hora',
        'caja',
        'sucursal',
        'empresa',
        'cajero',
        'tipo',
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
        'percepcion',
        'subGeneral',
        'totalPercepcion',
        'totalGlobal',
        'totalEfectivo',
        'diferencia',
        'sincro_id', // opcionalmente lo agregas al fillable
    ];

    // Define las relaciones con otras tablas si es necesario
    public function cajas()
    {
        return $this->belongsTo(Parametros::class, 'caja');
    }

    public function sucursales()
    {
        return $this->belongsTo(Sucursales::class, 'sucursal');
    }

    public function empresas()
    {
        return $this->belongsTo(Empresas::class, 'empresa');
    }

    public function usuarios()
    {
        return $this->belongsTo(User::class, 'cajero');
    }
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
