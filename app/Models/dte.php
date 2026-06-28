<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;


class dte extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['motivoContin', 'version', 'ambiente', 'tipoDte', 'numeroControl', 'codigoGeneracion', 'tipoModelo', 'tipoOperacion', 'tipoContingencia', 'fecEmi', 'horEmi', 'tipoMoneda', 'documentoRelacionado', 'emisor', 'receptor', 'otrosDocuentos', 'ventaTercero', 'venta', 'tocken', 'sello', 'estado', 'jsonDte', 'caja', 'sucursal', 'empresa','sincro_id'];

    // Genera sincro_id si no viene seteado
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->sincro_id)) {
                $model->sincro_id = (string) Str::uuid();
            }
        });
    } 

    protected function jsonDte(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                if ($value === null) return null;

                // Si viene array/obj -> a JSON
                $json = is_array($value) || is_object($value)
                    ? json_encode($value, JSON_UNESCAPED_UNICODE)
                    : (string) $value;

                // Evita doble compresión si ya viene comprimido
                if (is_string($value)) {
                    if (@gzuncompress($value) !== false) return $value;           // gzcompress (PHP)
                    if (@gzuncompress(substr($value, 4)) !== false) return $value; // COMPRESS() (MySQL)
                }

                return gzcompress($json);
            }
        );
    }


    public function tipoDte()
    {
        return $this->belongsTo(TipoDocumento::class, 'tipoDte');
    }

    public function ambiente()
    {
        return $this->belongsTo(AmbienteDestino::class, 'ambiente');
    }

    public function tipoOperacion()
    {
        return $this->belongsTo(TipoTransmision::class, 'tipoOperacion');
    }

    public function tipoContingencia()
    {
        return $this->belongsTo(TipoContigencia::class, 'tipoContingencia');
    }

    public function tipoModelo()
    {
        return $this->belongsTo(ModeloFacturacion::class, 'tipoModelo');
    }

    //public function empresa()
    //{
        //return $this->belongsTo(Empresas::class, 'empresa');
    //}

    public function emisor()
    {
        return $this->belongsTo(Sucursales::class, 'emisor');
    }



}
