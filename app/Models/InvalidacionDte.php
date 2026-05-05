<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;


class InvalidacionDte extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'tipo',
        'ambiente',
        'numero',
        'codigoGeneracion',
        'fecAnula',
        'horAnula',
        'emisor',
        'dte',
        'codigoGeneracionR',
        'tipoAnulacion',
        'motivoAnulacion',
        'responsable',
        'solicita',
        'caja',
        'sucursal',
        'empresa',
        'estado',
        'selloRecibido',
        'fhProcesamiento',
        'descripcionMsg',
        'json',
        'jsonRespuesta',
        'docFirmado',
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

    public function tipoInvalidacion()
    {
        return $this->belongsTo(TipoInvalidacion::class, 'tipoAnulacion');
    }
}
