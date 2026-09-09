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

    /**
     * Compresion transparente del Archivo DTE.
     *
     * Se guarda en el formato canonico gz64: (gzencode + Base64 envuelto como
     * string JSON valido). El formato anterior era gzcompress binario crudo,
     * que no es JSON valido pese a que la columna se declaro como json; los
     * registros historicos en ese formato -y los de MySQL COMPRESS(), con sus
     * cuatro bytes de encabezado- se siguen leyendo, porque descomprimir() los
     * reconoce. El resto de la aplicacion recibe siempre JSON normal.
     */
    protected function jsonDte(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => \App\Support\JsonComprimido::descomprimir($value),
            set: fn ($value) => \App\Support\JsonComprimido::comprimir($value),
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
