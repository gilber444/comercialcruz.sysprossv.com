<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ContingenciaDte extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['numero', 'version', 'ambiente', 'codigoGeneracion', 'fTransmision', 'hTransmision', 'emisor', 'empresa', 'fInicio', 'fFin', 'hInicio', 'hFin', 'tipoContingencia', 'motivoContingencia', 'json', 'jsonFirmado', 'estado', 'fechaHora', 'mensaje', 'selloRecibido', 'observaciones', 'jsonRespuesta', 'sincro_id'];

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
    public function sucursal()
    {
        return $this->belongsTo(Sucursales::class, 'emisor');
    }
    public function empresas()
    {
        return $this->belongsTo(Empresas::class, 'empresa');
    }
    public function tipoContin()
    {
        return $this->belongsTo(TipoContigencia::class, 'tipoContingencia');
    }

    public function contingenciaDteDetalles()
    {
        return $this->hasMany(ContingenciadteDetalles::class, 'contingencia');
    }

}
