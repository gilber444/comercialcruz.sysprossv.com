<?php

namespace App\Models;

use App\Models\ClaseDocumento;
use App\Models\LibroVentasConsumidor;
use App\Models\TipoDocumento;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;


class LibroVentasConsumidorDetalles extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'libro',
        'fechaEmision',
        'clase',
        'tipoDocumento',
        'numeroDocumento',
        'serieDocumento',
        'numeroControlDel',
        'numeroControlAl',
        'numeroDocumentoDel',
        'numeroDocumentoAl',
        'maquinaRegistradora',
        'ventasExenta',
        'ventasInternaExenta',
        'ventaNoSujera',
        'ventaGravadaLocal',
        'exportacionesDentro',
        'exportacionesFuera',
        'exportacionesServicios',
        'ventasZonaFranca',
        'ventaCuentaTerceros',
        'totalVentas',
        'anexo',
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

    public function libro()
    {
        return $this->belongsTo(LibroVentasConsumidor::class, 'libro');
    }

    public function tipoDocs()
    {
        return $this->belongsTo(TipoDocumento::class, 'tipoDocumento');
    }

    public function clases()
    {
        return $this->belongsTo(ClaseDocumento::class, 'clase');
    }
}
