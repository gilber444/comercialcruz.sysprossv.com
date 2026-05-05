<?php

namespace App\Models;

use App\Models\AmbienteDestino;
use App\Models\Empresas;
use App\Models\lotedteDetalles;
use App\Models\Sucursales;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;


class lotedte extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable =[
        'numero',
        'fecha',
        'hora',
        'ambiente',
        'idEnvio',
        'version',
        'sucursal',
        'empresa',
        'estado',
        'fhProcesamiento',
        'codigoLote',
        'codigoMsg',
        'descripcionMsg',
        'json',
        'jsonRespuesta',
        'sincro_id',
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

    public function empresa()
    {
        return $this->belongsTo(Empresas::class);
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursales::class);
    }

    public function lotedte_detalles()
    {
        return $this->hasMany(lotedteDetalles::class, 'lote');
    }
}
