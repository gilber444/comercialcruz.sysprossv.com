<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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

    /**
     * Compresion transparente: se guarda en el formato canonico gz64: y se
     * devuelve siempre JSON normal. Los valores historicos en gzcompress
     * binario, en MySQL COMPRESS() o en JSON plano se siguen leyendo.
     */
    protected function json(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => \App\Support\JsonComprimido::descomprimir($value),
            set: fn ($value) => \App\Support\JsonComprimido::comprimir($value),
        );
    }

    /**
     * Compresion transparente: se guarda en el formato canonico gz64: y se
     * devuelve siempre JSON normal. Los valores historicos en gzcompress
     * binario, en MySQL COMPRESS() o en JSON plano se siguen leyendo.
     */
    protected function jsonRespuesta(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => \App\Support\JsonComprimido::descomprimir($value),
            set: fn ($value) => \App\Support\JsonComprimido::comprimir($value),
        );
    }
}
