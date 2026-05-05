<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class precompra extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'tipo',
        'version',
        'ambiente',
        'tipoDte',
        'numeroControl',
        'codigoGeneracion',
        'tipoModelo',
        'tipoOperacion',
        'tipoContingencia',
        'fecEmi',
        'horEmi',
        'tipoMoneda',
        'documentoRelacionado',
        'emisor',
        'receptor',
        'sello',
        'totalNoSuj',
        'totalExenta',
        'totalGravada',
        'subTotalVentas',
        'descuNoSuj',
        'descuExenta',
        'descuGravada',
        'porcentajeDescuento',
        'totalDescu',
        'totalIva',
        'subTotal',
        'ivaPerci1',
        'ivaRete1',
        'reteRenta',
        'montoTotalOperacion',
        'totalNoGravado',
        'totalPagar',
        'estado',
        'jsonDte',
        'sincro_id'
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

    public function ambienteDestino()
    {
        return $this->belongsTo(AmbienteDestino::class, 'ambiente');
    }

    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumento::class, 'tipoDte');
    }

    public function modeloFacturacion()
    {
        return $this->belongsTo(ModeloFacturacion::class, 'tipoModelo');
    }

    public function tipoTransmision()
    {
        return $this->belongsTo(TipoTransmision::class, 'tipoOperacion');
    }

    public function tipoContingencia()
    {
        return $this->belongsTo(TipoContigencia::class, 'tipoContingencia');
    }

    public function Proveedores()
    {
        return $this->belongsTo(Proveedores::class, 'emisor');
    }

    public function empresaReceptor()
    {
        return $this->belongsTo(Empresas::class, 'receptor');
    }

    public function detalles()
    {
        return $this->hasMany(precompraDetalle::class, 'precompra', 'id');
    }

}
