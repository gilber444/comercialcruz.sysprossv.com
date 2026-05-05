<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;


class SolicitudesDetalles extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'solicitud',
        'producto',
        'medida',
        'unidad',
        'origen',
        'destino',
        'cantidad',
        'contenedor',
        'descargar',
        'costo',
        'total',
        'realizado',
        'autorizado',
        'despachado',
        'ingresado',
        'estado',
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

    public function Rproductos()
    {
        return $this->belongsTo(Productos::class, 'producto');
    }

    public function Rmedidas()
    {
        return $this->belongsTo(Medidas::class, 'medida');
    }

    public function Rproducto()
    {
        return $this->belongsTo(Productos::class, 'producto');
    }

    public function Rsolicitudes()
    {
        return $this->belongsTo(Solicitudes::class, 'solicitud');
    }
}