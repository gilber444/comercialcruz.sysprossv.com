<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;


class RecepcionDte extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'dte',
        'fechaRecepcion',
        'horaRecepcion',
        'selloRecibido',
        'fhProcesamiento',
        'estado',
        'observaciones',
        'josn',
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
}
